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

        // Resize to 384px to keep vision token usage minimal (~8.5 neurons per search)
        $imageBytes = self::prepareImageBytes($imagePath, 384);
        if (!$imageBytes) {
            return null;
        }

        $acc = self::getAccountId();
        $token = self::getApiToken();
        $url = "https://api.cloudflare.com/client/v4/accounts/{$acc}/ai/run/" . self::MODEL_VISION;

        $categories = self::getActiveStoreCategories();
        $prompt = "You are an e-commerce catalog visual search assistant. The store inventory covers these product categories: {$categories}. Look at the product in the image.\n"
            . "1. If the item clearly does NOT belong to ANY of these categories (e.g. rubik's cube, toys, tools, hardware, animals, electronics, vehicles), respond ONLY with: REJECT_UNRELATED\n"
            . "2. Otherwise, extract structured information in this EXACT format (do not add conversational prose):\n"
            . "BRAND: [Brand name if visible on packaging/label, or NONE]\n"
            . "TYPE: [Product type, e.g. Earrings, Shampoo, Body Wash, Face Cream, Body Oil, Necklace, Ring, Abaya, Bag]\n"
            . "KEYWORDS: [3-5 key distinct product model or design words, e.g. snowflake tassel gold or cocoa radiant or soft cream]";

        try {
            $response = Http::timeout(12)
                ->withToken($token)
                ->post($url, [
                    'image' => $imageBytes,
                    'prompt' => $prompt,
                    'max_tokens' => 60,
                    'temperature' => 0.1,
                ]);

            if ($response->successful()) {
                $text = trim($response->json('result.response') ?? '');
                if (!empty($text)) {
                    if (str_contains($text, 'REJECT_UNRELATED')) {
                        return [
                            'is_relevant' => false,
                            'brand' => null,
                            'type' => null,
                            'keywords' => '',
                            'raw' => $text,
                        ];
                    }

                    $brand = null;
                    $type = null;
                    $keywords = '';

                    if (preg_match('/(?:\*+\s*)?(?:\*\*)?BRAND(?:\*\*)?:\s*([^\r\n]+)/i', $text, $bm)) {
                        $b = trim(preg_replace('/[\*#`]+/', '', $bm[1]));
                        $b = trim(preg_replace('/\s*\(.*?\)/', '', $b));
                        if (!in_array(strtoupper($b), ['NONE', 'N/A', 'UNKNOWN', 'NULL', 'NO BRAND', ''])) {
                            $brand = $b;
                        }
                    }

                    if (preg_match('/(?:\*+\s*)?(?:\*\*)?TYPE(?:\*\*)?:\s*([^\r\n]+)/i', $text, $tm)) {
                        $t = trim(preg_replace('/[\*#`]+/', '', $tm[1]));
                        $t = trim(preg_replace('/\s*\(.*?\)/', '', $t));
                        if (!in_array(strtoupper($t), ['NONE', 'N/A', 'UNKNOWN', 'NULL', ''])) {
                            $type = $t;
                        }
                    }

                    if (preg_match('/(?:\*+\s*)?(?:\*\*)?KEYWORDS(?:\*\*)?:\s*([^\r\n]+)/i', $text, $km)) {
                        $k = trim(preg_replace('/[\*#`]+/', '', $km[1]));
                        $k = trim(preg_replace('/\s*\(.*?\)/', '', $k));
                        $keywords = $k;
                    }

                    // Fallback keywords if structured format was partially matched
                    if (empty($keywords) && empty($brand) && empty($type)) {
                        $cleanKeywords = preg_replace('/(the item in the image is a|the brand is|the product is|from the brand)/i', '', $text);
                        $keywords = trim(preg_replace('/[^a-zA-Z0-9\s\-\.]/', ' ', $cleanKeywords));
                    }

                    return [
                        'is_relevant' => true,
                        'brand' => $brand,
                        'type' => $type,
                        'keywords' => $keywords,
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
