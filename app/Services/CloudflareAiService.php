<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CloudflareAiService
{
    public const MODEL_VISION = '@cf/meta/llama-3.2-11b-vision-instruct';
    public const MODEL_CLIP = '@cf/meta/llama-3.2-11b-vision-instruct';
    public const MODEL_EMBEDDING = '@cf/baai/bge-base-en-v1.5';
    public const MODEL_RESNET = '@cf/microsoft/resnet-50';

    /**
     * Check if AI Visual Search is enabled in settings or .env
     */
    public static function isEnabled(): bool
    {
        // Keep unit tests fast and isolated without burning Cloudflare API quota
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return false;
        }

        $setting = Setting::get('visual_search_ai_enabled');
        if ($setting !== null) {
            return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) env('CLOUDFLARE_AI_ENABLED', true);
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
     * Check if AI search is ready to be used
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

        $url = "https://api.cloudflare.com/client/v4/accounts/{$acc}/ai/run/" . self::MODEL_EMBEDDING;
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
                        'model' => self::MODEL_VISION,
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
     * Prepare image as an array of byte integers (number[]) resized to max 640px for fast upload
     */
    public static function prepareImageBytes(string $imagePath, int $maxDim = 640): ?array
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
                $raw = @file_get_contents($imagePath);
                return $raw ? array_values(unpack('C*', $raw)) : null;
            }

            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min(1.0, $maxDim / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));

            $thumb = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($thumb, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

            ob_start();
            imagejpeg($thumb, null, 85);
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
     * Get active store category names from database for dynamic AI prompt context.
     * Automatically adapts when new categories (ladies fashion, bags, watches, shoes, etc.) are added.
     * Cached for 1 hour to ensure zero database latency on searches.
     */
    public static function getActiveStoreCategories(): string
    {
        try {
            return Cache::remember('active_store_category_names', 3600, function () {
                $categories = Category::where('is_active', true)
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                if (!empty($categories)) {
                    return implode(', ', $categories);
                }

                return 'cosmetics, skincare, beauty, jewelry, fashion, accessories';
            });
        } catch (Throwable $e) {
            return 'cosmetics, skincare, beauty, jewelry, fashion, accessories';
        }
    }

    /**
     * Analyze image with Cloudflare Vision AI:
     * 1. Rejects completely unrelated objects (e.g. Rubik's cube, toys, electronics, furniture, animals)
     * 2. Extracts product brand, title keywords, and category for exact matching
     *
     * @return array|null ['is_relevant' => bool, 'keywords' => string]
     */
    public static function analyzeImageForVisualSearch(string $imagePath): ?array
    {
        if (!self::isReady()) {
            return null;
        }

        $imageBytes = self::prepareImageBytes($imagePath, 512);
        if (!$imageBytes) {
            return null;
        }

        $acc = self::getAccountId();
        $token = self::getApiToken();
        $url = "https://api.cloudflare.com/client/v4/accounts/{$acc}/ai/run/" . self::MODEL_VISION;

        $categories = self::getActiveStoreCategories();
        $prompt = "You are an AI catalog classifier for an e-commerce shop whose inventory covers these product categories: {$categories}. Examine the product in the uploaded image carefully. If the item clearly does NOT match or belong to ANY of these categories (for example: toys, rubik's cube, tools, electronics, animals, food/produce, vehicles, heavy hardware, furniture), respond ONLY with the word \"REJECT_UNRELATED\". If it matches or is related to any of the store's categories, output ONLY comma-separated essential keywords (brand name, specific product type, and distinct visual features, e.g.: Nivea, soft cream or snowflake, tassel, earrings, gold). Do NOT write conversational sentences, explanations, or filler words.";

        try {
            $response = Http::timeout(12)
                ->withToken($token)
                ->post($url, [
                    'image' => $imageBytes,
                    'prompt' => $prompt,
                ]);

            if ($response->successful()) {
                $text = trim($response->json('result.response') ?? '');
                if (!empty($text)) {
                    if (str_contains($text, 'REJECT_UNRELATED')) {
                        return [
                            'is_relevant' => false,
                            'keywords' => '',
                            'raw' => $text,
                        ];
                    }

                    // Clean and sanitize keywords (remove filler words)
                    $cleanKeywords = preg_replace('/(the item in the image is a|the brand is|the product is|from the brand)/i', '', $text);
                    $cleanKeywords = trim(preg_replace('/[^a-zA-Z0-9\s\-\.]/', ' ', $cleanKeywords));

                    return [
                        'is_relevant' => true,
                        'keywords' => $cleanKeywords,
                        'raw' => $text,
                    ];
                }
            }

            Log::warning('Cloudflare Vision API response error: ' . $response->body());
            return null;
        } catch (Throwable $e) {
            Log::warning('Cloudflare Vision API exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate image class feature embedding using ResNet-50
     */
    public static function generateImageEmbedding(string $imagePath): ?array
    {
        if (!self::isReady()) {
            return null;
        }

        $imageBytes = self::prepareImageBytes($imagePath, 384);
        if (!$imageBytes) {
            return null;
        }

        $acc = self::getAccountId();
        $token = self::getApiToken();
        $url = "https://api.cloudflare.com/client/v4/accounts/{$acc}/ai/run/" . self::MODEL_RESNET;

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($url, [
                    'image' => $imageBytes,
                ]);

            if ($response->successful()) {
                $labels = $response->json('result') ?? [];
                if (is_array($labels) && !empty($labels)) {
                    $map = [];
                    foreach ($labels as $l) {
                        if (!empty($l['label'])) {
                            $map[$l['label']] = round((float) ($l['score'] ?? 0.0), 4);
                        }
                    }
                    return $map;
                }
            }

            return null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Generate text embedding for search tokens
     */
    public static function generateTextEmbedding(string $text): ?array
    {
        if (!self::isReady()) {
            return null;
        }

        $acc = self::getAccountId();
        $token = self::getApiToken();
        $url = "https://api.cloudflare.com/client/v4/accounts/{$acc}/ai/run/" . self::MODEL_EMBEDDING;

        try {
            $response = Http::timeout(8)
                ->withToken($token)
                ->post($url, [
                    'text' => [trim($text)],
                ]);

            if ($response->successful()) {
                $emb = $response->json('result.data.0');
                if (is_array($emb)) {
                    return array_map('floatval', $emb);
                }
            }

            return null;
        } catch (Throwable $e) {
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
