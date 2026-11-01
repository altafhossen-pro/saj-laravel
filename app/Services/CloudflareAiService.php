<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CloudflareAiService
{
    public const MODEL_CLIP = '@cf/openai/clip-vit-base-patch32';

    /**
     * Check if AI Visual Search is enabled in settings or .env
     */
    public static function isEnabled(): bool
    {
        $setting = Setting::get('visual_search_ai_enabled');
        if ($setting !== null) {
            return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) env('CLOUDFLARE_AI_ENABLED', false);
    }

    /**
     * Get Cloudflare Account ID
     */
    public static function getAccountId(): ?string
    {
        $val = Setting::get('cloudflare_account_id');
        if (!empty($val)) {
            return trim($val);
        }

        return env('CLOUDFLARE_ACCOUNT_ID') ? trim(env('CLOUDFLARE_ACCOUNT_ID')) : null;
    }

    /**
     * Get Cloudflare Workers AI API Token
     */
    public static function getApiToken(): ?string
    {
        $val = Setting::get('cloudflare_ai_token');
        if (!empty($val)) {
            return trim($val);
        }

        return env('CLOUDFLARE_AI_TOKEN') ? trim(env('CLOUDFLARE_AI_TOKEN')) : null;
    }

    /**
     * Check if credentials are fully configured
     */
    public static function isConfigured(): bool
    {
        return !empty(self::getAccountId()) && !empty(self::getApiToken());
    }

    /**
     * Check if AI search is fully ready to be used
     */
    public static function isReady(): bool
    {
        return self::isEnabled() && self::isConfigured();
    }

    /**
     * Test connection to Cloudflare Workers AI with credentials
     */
    public static function testConnection(?string $accountId = null, ?string $apiToken = null): array
    {
        $acc = $accountId ?: self::getAccountId();
        $token = $apiToken ?: self::getApiToken();

        if (empty($acc) || empty($token)) {
            return [
                'success' => false,
                'message' => 'Account ID and API Token are required.',
                'latency_ms' => null,
            ];
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$acc}/ai/run/" . self::MODEL_CLIP;
        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($url, [
                    'text' => ['cosmetics perfume beauty jewelry'],
                ]);

            $latency = round((microtime(true) - $startTime) * 1000, 0);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['result']) || !empty($data['success'])) {
                    return [
                        'success' => true,
                        'message' => 'Cloudflare Workers AI connected successfully!',
                        'latency_ms' => $latency,
                        'model' => self::MODEL_CLIP,
                    ];
                }
            }

            $errMsg = $response->json('errors.0.message') ?: ($response->body() ?: 'HTTP ' . $response->status());
            return [
                'success' => false,
                'message' => 'Cloudflare API error: ' . $errMsg,
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 0);
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'latency_ms' => $latency,
            ];
        }
    }

    /**
     * Prepare image as an array of byte integers (number[]) resized to 224x224 (native CLIP resolution)
     */
    protected static function prepareImageBytes(string $imagePath): ?array
    {
        if (!file_exists($imagePath) || !is_readable($imagePath)) {
            return null;
        }

        try {
            $info = @getimagesize($imagePath);
            if (!$info) {
                return null;
            }

            $src = null;
            $mime = $info['mime'] ?? '';
            if ($mime === 'image/jpeg' || $mime === 'image/pjpeg') {
                $src = @imagecreatefromjpeg($imagePath);
            } elseif ($mime === 'image/png') {
                $src = @imagecreatefrompng($imagePath);
            } elseif ($mime === 'image/webp') {
                $src = @imagecreatefromwebp($imagePath);
            }

            if (!$src) {
                // Fallback to raw bytes directly
                $raw = @file_get_contents($imagePath);
                return $raw ? array_values(unpack('C*', $raw)) : null;
            }

            // Standardize to 224x224 square for ultra-fast upload (~8KB-12KB) and optimal CLIP ViT input
            $thumb = imagecreatetruecolor(224, 224);
            $w = imagesx($src);
            $h = imagesy($src);
            imagecopyresampled($thumb, $src, 0, 0, 0, 0, 224, 224, $w, $h);

            ob_start();
            imagejpeg($thumb, null, 88);
            $jpgData = ob_get_clean();

            imagedestroy($thumb);
            imagedestroy($src);

            if (!$jpgData) {
                return null;
            }

            return array_values(unpack('C*', $jpgData));
        } catch (Throwable $e) {
            Log::warning('Image prep for Cloudflare AI failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate 512-dimensional vector embedding for an image using Cloudflare Workers AI
     */
    public static function generateImageEmbedding(string $imagePath): ?array
    {
        if (!self::isConfigured()) {
            return null;
        }

        $imageBytes = self::prepareImageBytes($imagePath);
        if (!$imageBytes) {
            return null;
        }

        $acc = self::getAccountId();
        $token = self::getApiToken();
        $url = "https://api.cloudflare.com/client/v4/accounts/{$acc}/ai/run/" . self::MODEL_CLIP;

        try {
            $response = Http::timeout(12)
                ->withToken($token)
                ->post($url, [
                    'image' => $imageBytes,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // Workers AI returns { result: [...] } or direct array of floats
                $embedding = $data['result'] ?? null;
                if (is_array($embedding) && !empty($embedding)) {
                    // Normalize vector values to standard floats
                    return array_map('floatval', $embedding);
                }
            }

            Log::warning('Cloudflare Workers AI embedding error: ' . $response->body());
            return null;
        } catch (Throwable $e) {
            Log::warning('Cloudflare Workers AI request exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compute Cosine Similarity between two vector arrays (0.0 to 1.0)
     */
    public static function computeCosineSimilarity(array $vecA, array $vecB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $len = min(count($vecA), count($vecB));

        for ($i = 0; $i < $len; $i++) {
            $a = (float) $vecA[$i];
            $b = (float) $vecB[$i];
            $dot += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        $similarity = $dot / (sqrt($normA) * sqrt($normB));
        return min(1.0, max(0.0, (float) $similarity));
    }
}
