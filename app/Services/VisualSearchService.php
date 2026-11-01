<?php

namespace App\Services;

use App\Models\Product\Product;
use App\Models\ProductVisualIndex;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VisualSearchService
{
    /**
     * Compute a visual fingerprint for a given local file path or image string.
     */
    public static function createFingerprintFromPath(string $filePath): ?array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $image = self::loadImageResource($filePath);
        if (!$image) {
            return null;
        }

        $origW = imagesx($image);
        $origH = imagesy($image);
        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($image);
            return null;
        }

        $aspectRatio = round($origW / $origH, 2);

        // 1. Calculate 64-bit Difference Hash (dHash) - 9x8 grayscale
        $dhash = self::computeDHash($image, $origW, $origH);

        // 2. Calculate 256-bit Detail Difference Hash (dHash Detail) - 17x16 grayscale for fine patterns & logos
        $dhashDetail = self::computeDHashDetail($image, $origW, $origH);

        // 3. Calculate 64-bit Average Hash (aHash) - 8x8 grayscale
        $ahash = self::computeAHash($image, $origW, $origH);

        // 4. Calculate 64-bit Center-Crop Difference Hash (center 75% crop) to focus on product and ignore background
        $centerDHash = self::computeCenterDHash($image, $origW, $origH);

        // 5. Calculate 52-bin Multi-zone Color Signature (Weighted center focus)
        $colorSignature = self::computeColorSignature($image, $origW, $origH);

        imagedestroy($image);

        return [
            'dhash' => $dhash,
            'dhash_detail' => $dhashDetail,
            'ahash' => $ahash,
            'center_dhash' => $centerDHash,
            'color_signature' => $colorSignature,
            'aspect_ratio' => $aspectRatio,
        ];
    }

    /**
     * Compute Difference Hash (dHash): resilient to scaling and brightness gradients.
     */
    protected static function computeDHash($image, int $origW, int $origH): string
    {
        $thumb = imagecreatetruecolor(9, 8);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, 9, 8, $origW, $origH);

        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            $prevLum = null;
            for ($x = 0; $x < 9; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);

                if ($x > 0) {
                    $bits .= ($prevLum > $lum) ? '1' : '0';
                }
                $prevLum = $lum;
            }
        }

        imagedestroy($thumb);
        return $bits;
    }

    /**
     * Compute 256-bit Detail Difference Hash (17x16): captures finer edge contours and logo patterns.
     */
    protected static function computeDHashDetail($image, int $origW, int $origH): string
    {
        $thumb = imagecreatetruecolor(17, 16);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, 17, 16, $origW, $origH);

        $bits = '';
        for ($y = 0; $y < 16; $y++) {
            $prevLum = null;
            for ($x = 0; $x < 17; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);

                if ($x > 0) {
                    $bits .= ($prevLum > $lum) ? '1' : '0';
                }
                $prevLum = $lum;
            }
        }

        imagedestroy($thumb);
        return $bits;
    }

    /**
     * Compute 64-bit Center-Crop Difference Hash (center 75% crop):
     * Eliminates table surfaces, hands, or borders in uploaded user photos.
     */
    protected static function computeCenterDHash($image, int $origW, int $origH): string
    {
        $cw = max(1, (int) round($origW * 0.75));
        $ch = max(1, (int) round($origH * 0.75));
        $cx = max(0, (int) round(($origW - $cw) / 2));
        $cy = max(0, (int) round(($origH - $ch) / 2));

        $thumb = imagecreatetruecolor(9, 8);
        imagecopyresampled($thumb, $image, 0, 0, $cx, $cy, 9, 8, $cw, $ch);

        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            $prevLum = null;
            for ($x = 0; $x < 9; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);

                if ($x > 0) {
                    $bits .= ($prevLum > $lum) ? '1' : '0';
                }
                $prevLum = $lum;
            }
        }

        imagedestroy($thumb);
        return $bits;
    }

    /**
     * Compute Average Hash (aHash): captures light/dark shape distribution.
     */
    protected static function computeAHash($image, int $origW, int $origH): string
    {
        $thumb = imagecreatetruecolor(8, 8);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, 8, 8, $origW, $origH);

        $pixels = [];
        $totalLum = 0;
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);
                $pixels[] = $lum;
                $totalLum += $lum;
            }
        }

        $avgLum = $totalLum / 64;
        $bits = '';
        foreach ($pixels as $lum) {
            $bits .= ($lum >= $avgLum) ? '1' : '0';
        }

        imagedestroy($thumb);
        return $bits;
    }

    /**
     * Compute 52-bin Multi-Zone Normalized Color Signature.
     */
    protected static function computeColorSignature($image, int $origW, int $origH): array
    {
        $sampleSize = 32;
        $thumb = imagecreatetruecolor($sampleSize, $sampleSize);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $sampleSize, $sampleSize, $origW, $origH);

        $bins = array_fill(0, 52, 0.0);
        $totalWeight = 0.0;

        $centerXMin = (int) round($sampleSize * 0.20);
        $centerXMax = (int) round($sampleSize * 0.80);
        $centerYMin = (int) round($sampleSize * 0.20);
        $centerYMax = (int) round($sampleSize * 0.80);

        for ($y = 0; $y < $sampleSize; $y++) {
            for ($x = 0; $x < $sampleSize; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $r = (($rgb >> 16) & 0xFF) / 255.0;
                $g = (($rgb >> 8) & 0xFF) / 255.0;
                $b = ($rgb & 0xFF) / 255.0;

                // Center product subject receives 2x weight compared to borders
                $isCenter = ($x >= $centerXMin && $x <= $centerXMax && $y >= $centerYMin && $y <= $centerYMax);
                $weight = $isCenter ? 2.0 : 1.0;

                $max = max($r, $g, $b);
                $min = min($r, $g, $b);
                $delta = $max - $min;

                $v = $max;
                $s = ($max > 0.0) ? ($delta / $max) : 0.0;

                // Neutral / Grayscale check (low saturation or very low/high brightness)
                if ($s < 0.15 || $v < 0.10) {
                    if ($v < 0.20) {
                        $bin = 0; // Black / Deep dark
                    } elseif ($v < 0.50) {
                        $bin = 1; // Dark Gray
                    } elseif ($v < 0.80) {
                        $bin = 2; // Light Gray
                    } else {
                        $bin = 3; // White / Bright neutral
                    }
                } else {
                    if ($delta == 0.0) {
                        $h = 0.0;
                    } elseif ($max == $r) {
                        $h = 60.0 * fmod((($g - $b) / $delta), 6);
                    } elseif ($max == $g) {
                        $h = 60.0 * ((($b - $r) / $delta) + 2);
                    } else {
                        $h = 60.0 * ((($r - $g) / $delta) + 4);
                    }

                    if ($h < 0.0) {
                        $h += 360.0;
                    }

                    $hueSector = (int) min(11, floor($h / 30.0)); // 12 sectors (0..11)
                    $satBand = ($s >= 0.50) ? 1 : 0;              // 2 bands
                    $valBand = ($v >= 0.50) ? 1 : 0;              // 2 bands

                    $bin = 4 + ($hueSector * 4) + ($satBand * 2) + $valBand;
                }

                $bins[$bin] += $weight;
                $totalWeight += $weight;
            }
        }

        imagedestroy($thumb);

        // Normalize signature so sum = 1.0
        if ($totalWeight > 0) {
            foreach ($bins as $k => $val) {
                $bins[$k] = round($val / $totalWeight, 4);
            }
        }

        return $bins;
    }

    /**
     * Compute Hamming distance between two binary hash strings.
     */
    public static function computeHammingDistance(string $hashA, string $hashB): int
    {
        $len = min(strlen($hashA), strlen($hashB));
        $diff = 0;
        for ($i = 0; $i < $len; $i++) {
            if ($hashA[$i] !== $hashB[$i]) {
                $diff++;
            }
        }
        return $diff + abs(strlen($hashA) - strlen($hashB));
    }

    /**
     * Compute histogram intersection similarity between two normalized color signatures (0.0 to 1.0).
     */
    public static function computeColorSimilarity(array $sigA, array $sigB): float
    {
        $intersection = 0.0;
        $maxLen = max(count($sigA), count($sigB));
        for ($i = 0; $i < $maxLen; $i++) {
            $a = (float) ($sigA[$i] ?? 0.0);
            $b = (float) ($sigB[$i] ?? 0.0);
            $intersection += min($a, $b);
        }
        return min(1.0, max(0.0, $intersection));
    }

    /**
     * Compare query fingerprint and optional text against an indexed product image.
     * Returns a composite score from 0.0 to 100.0%.
     */
    public static function calculateMatchScore(array $queryFp, ProductVisualIndex $index, ?string $extractedText = null): float
    {
        // 1. dHash match (difference gradients, 64-bit)
        $dDist = self::computeHammingDistance($queryFp['dhash'], $index->dhash);
        $dScore = max(0.0, (1.0 - ($dDist / 32.0)));

        // 2. Detail dHash match (256-bit fine-grained contours & logos)
        $detailScore = $dScore;
        if (!empty($queryFp['dhash_detail']) && !empty($index->dhash_detail)) {
            $detailDist = self::computeHammingDistance($queryFp['dhash_detail'], $index->dhash_detail);
            $detailScore = max(0.0, (1.0 - ($detailDist / 128.0)));
        }

        // 3. Center-crop dHash match (product center 75%, invariant to backgrounds)
        $centerScore = $dScore;
        if (!empty($queryFp['center_dhash']) && !empty($index->center_dhash)) {
            $centerDist = self::computeHammingDistance($queryFp['center_dhash'], $index->center_dhash);
            $centerScore = max(0.0, (1.0 - ($centerDist / 32.0)));
        }

        // Cross foreground invariance: user center crop vs catalog image, and vice-versa
        // Essential when user takes a photo on a desk/keyboard/hand
        if (!empty($queryFp['center_dhash']) && !empty($index->dhash)) {
            $crossDist1 = self::computeHammingDistance($queryFp['center_dhash'], $index->dhash);
            $crossScore1 = max(0.0, (1.0 - ($crossDist1 / 32.0)));
            $centerScore = max($centerScore, $crossScore1);
        }
        if (!empty($queryFp['dhash']) && !empty($index->center_dhash)) {
            $crossDist2 = self::computeHammingDistance($queryFp['dhash'], $index->center_dhash);
            $crossScore2 = max(0.0, (1.0 - ($crossDist2 / 32.0)));
            $centerScore = max($centerScore, $crossScore2);
        }

        // 4. aHash match (overall light/dark structure, 64-bit)
        $aDist = self::computeHammingDistance($queryFp['ahash'], $index->ahash);
        $aScore = max(0.0, (1.0 - ($aDist / 32.0)));

        // 5. Color signature match
        $cScore = 0.0;
        if (!empty($queryFp['color_signature']) && !empty($index->color_signature)) {
            $cScore = self::computeColorSimilarity($queryFp['color_signature'], $index->color_signature);
        }

        // Composite visual match score:
        // Center Foreground Focus (35%) + Detail/Edge (25%) + Global Structure (15%) + Color Harmony (25%)
        $bestStructure = max($centerScore, $dScore);
        $visualComposite = ($bestStructure * 0.35) + ($detailScore * 0.25) + ($aScore * 0.15) + ($cScore * 0.25);

        // Aspect ratio boost/penalty: if ratios are very close, slight confidence boost
        $queryRatio = $queryFp['aspect_ratio'] ?? 1.0;
        $targetRatio = $index->aspect_ratio ?? 1.0;
        $ratioDelta = abs($queryRatio - $targetRatio);
        if ($ratioDelta <= 0.15) {
            $visualComposite = min(1.0, $visualComposite * 1.05);
        }

        $baseScore = $visualComposite * 100.0;

        // 6. Text / OCR / SKU / Barcode boost
        if (!empty($extractedText) && !empty($index->search_text)) {
            $textBoost = self::calculateTextBoost($extractedText, $index->search_text);
            $baseScore += $textBoost;
        }

        return min(100.0, max(0.0, round($baseScore, 1)));
    }

    /**
     * Calculate score boost when text found in the image (or filename) matches product text/SKU/brand.
     */
    protected static function calculateTextBoost(string $queryText, string $indexedText): float
    {
        $q = strtolower(trim($queryText));
        $target = strtolower(trim($indexedText));
        if ($q === '' || $target === '')
            return 0.0;

        $boost = 0.0;

        // Split query text into tokens (alphanumeric words)
        preg_match_all('/[a-z0-9_\-\.]{2,}/i', $q, $matches);
        $tokens = array_unique($matches[0] ?? []);

        foreach ($tokens as $token) {
            $tok = strtolower($token);
            if (strlen($tok) < 3)
                continue;

            // Direct word boundary or substring match
            if (str_contains($target, $tok)) {
                // 1. Exact Global Barcode match (EAN-13, EAN-8, UPC-A, 6-14 digits)
                if (preg_match('/^\d{6,14}$/', $tok)) {
                    $boost += 85.0; // High confidence barcode match
                } elseif (preg_match('/[0-9]/', $tok) && preg_match('/[a-z]/i', $tok) && strlen($tok) >= 4) {
                    // 2. Strong SKU match (contains letters and digits)
                    $boost += 60.0;
                } else {
                    // 3. Brand or product title keyword match
                    $boost += 25.0;
                }
            }
        }

        return min(95.0, $boost);
    }

    /**
     * Search product catalog using an uploaded image and optional extracted OCR text.
     */
    public static function searchByImage(string $uploadedTempPath, int $limit = 12, float $minScore = 25.0, ?string $extractedText = null): array
    {
        $queryFp = self::createFingerprintFromPath($uploadedTempPath);
        if (!$queryFp) {
            return [];
        }

        // Fetch all visual indexes with active products
        $indexes = ProductVisualIndex::with(['product.brand', 'product.category'])
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->get();

        $productMatches = [];

        foreach ($indexes as $index) {
            $product = $index->product;
            if (!$product)
                continue;

            $score = self::calculateMatchScore($queryFp, $index, $extractedText);

            if ($score >= $minScore) {
                $productId = $product->id;
                // Keep the best matching image score for each product
                if (!isset($productMatches[$productId]) || $score > $productMatches[$productId]['score']) {
                    $productMatches[$productId] = [
                        'product' => $product,
                        'score' => $score,
                        'matched_image' => $index->image_path,
                        'matched_source' => $index->source_type,
                    ];
                }
            }
        }

        // Sort desc by similarity score
        uasort($productMatches, fn($a, $b) => $b['score'] <=> $a['score']);

        $sortedMatches = array_values($productMatches);
        if (empty($sortedMatches)) {
            return [];
        }

        $topScore = $sortedMatches[0]['score'];

        // If top item doesn't reach at least 20%, no confident match
        if ($topScore < 20.0) {
            return [];
        }

        // Adaptive Quality Gate:
        // 1. If top match is an exact Barcode/SKU/Text match (>= 80%):
        //    Keep the exact match at the top, and also keep other visually matching products (>= 15%)
        // 2. If top match is a strong visual match (60% - 79%):
        //    Keep items within 25% of top match to prevent false positives (e.g. Dove shampoo vs Laikou Sakura)
        // 3. If top match is moderate (20% - 59%):
        //    Keep items with at least 20% similarity
        if ($topScore >= 80.0) {
            $cutoff = 15.0;
        } elseif ($topScore >= 60.0) {
            $cutoff = max(25.0, $topScore - 25.0);
        } else {
            $cutoff = 20.0;
        }

        $filtered = array_filter($sortedMatches, fn($item) => $item['score'] >= $cutoff);

        return array_slice(array_values($filtered), 0, $limit);
    }

    /**
     * Index a single product's featured, gallery, and variant images, with full SKU & keyword metadata.
     */
    public static function indexProduct(Product $product): int
    {
        // Remove existing indexes for this product to keep index clean
        ProductVisualIndex::where('product_id', $product->id)->delete();

        $product->loadMissing(['category', 'brand', 'variants']);

        // Build base searchable keywords for the product
        $baseKeywords = [
            $product->title,
            $product->brand?->name,
            $product->category?->name,
            $product->slug,
        ];
        if (is_array($product->tags)) {
            $baseKeywords = array_merge($baseKeywords, $product->tags);
        }

        // Inherit all variant SKUs, barcodes, and attributes into base keywords
        // Ensures featured and gallery images also match when a physical product variant is scanned
        if ($product->variants && $product->variants->isNotEmpty()) {
            foreach ($product->variants as $variant) {
                if (!empty($variant->sku)) {
                    $baseKeywords[] = $variant->sku;
                }
                if (!empty($variant->barcode)) {
                    $baseKeywords[] = $variant->barcode;
                }
                if (is_array($variant->attributes)) {
                    $baseKeywords = array_merge($baseKeywords, array_values($variant->attributes));
                }
            }
        }

        $baseSearchText = strtolower(implode(' ', array_unique(array_filter($baseKeywords))));

        $indexedPaths = [];
        $indexedCount = 0;

        // 1. Index Featured Image
        if (!empty($product->featured_image)) {
            $fullPath = self::resolveProductImagePath($product->featured_image);
            if ($fullPath && file_exists($fullPath)) {
                $fp = self::createFingerprintFromPath($fullPath);
                if ($fp) {
                    ProductVisualIndex::create([
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'image_path' => $product->featured_image,
                        'is_featured' => true,
                        'source_type' => 'featured',
                        'dhash' => $fp['dhash'],
                        'dhash_detail' => $fp['dhash_detail'] ?? null,
                        'ahash' => $fp['ahash'],
                        'center_dhash' => $fp['center_dhash'] ?? null,
                        'color_signature' => $fp['color_signature'],
                        'aspect_ratio' => $fp['aspect_ratio'],
                        'search_text' => $baseSearchText,
                    ]);
                    $indexedPaths[$product->featured_image] = true;
                    $indexedCount++;
                }
            }
        }

        // 2. Index Gallery Images
        $gallery = is_array($product->gallery)
            ? $product->gallery
            : (is_string($product->gallery) ? json_decode($product->gallery, true) : []);

        if (is_array($gallery)) {
            foreach ($gallery as $gImg) {
                if (empty($gImg) || isset($indexedPaths[$gImg]))
                    continue;

                $fullPath = self::resolveProductImagePath($gImg);
                if ($fullPath && file_exists($fullPath)) {
                    $fp = self::createFingerprintFromPath($fullPath);
                    if ($fp) {
                        ProductVisualIndex::create([
                            'product_id' => $product->id,
                            'variant_id' => null,
                            'image_path' => $gImg,
                            'is_featured' => false,
                            'source_type' => 'gallery',
                            'dhash' => $fp['dhash'],
                            'dhash_detail' => $fp['dhash_detail'] ?? null,
                            'ahash' => $fp['ahash'],
                            'center_dhash' => $fp['center_dhash'] ?? null,
                            'color_signature' => $fp['color_signature'],
                            'aspect_ratio' => $fp['aspect_ratio'],
                            'search_text' => $baseSearchText,
                        ]);
                        $indexedPaths[$gImg] = true;
                        $indexedCount++;
                    }
                }
            }
        }

        // 3. Index Variant Images with Variant SKU, Barcode, and Attributes
        if ($product->variants && $product->variants->isNotEmpty()) {
            foreach ($product->variants as $variant) {
                $variantKeywords = array_filter([
                    $variant->sku,
                    $variant->barcode,
                    is_array($variant->attributes) ? implode(' ', array_values($variant->attributes)) : null,
                ]);
                $variantSearchText = trim($baseSearchText . ' ' . strtolower(implode(' ', $variantKeywords)));

                $variantImages = [];
                if (!empty($variant->image)) {
                    $variantImages[] = $variant->image;
                }
                if (!empty($variant->images)) {
                    $vImgs = is_array($variant->images)
                        ? $variant->images
                        : (is_string($variant->images) ? json_decode($variant->images, true) : []);
                    if (is_array($vImgs)) {
                        $variantImages = array_merge($variantImages, $vImgs);
                    }
                }

                foreach ($variantImages as $vImg) {
                    if (empty($vImg) || isset($indexedPaths[$vImg]))
                        continue;

                    $fullPath = self::resolveProductImagePath($vImg);
                    if ($fullPath && file_exists($fullPath)) {
                        $fp = self::createFingerprintFromPath($fullPath);
                        if ($fp) {
                            ProductVisualIndex::create([
                                'product_id' => $product->id,
                                'variant_id' => $variant->id,
                                'image_path' => $vImg,
                                'is_featured' => false,
                                'source_type' => 'variant',
                                'dhash' => $fp['dhash'],
                                'dhash_detail' => $fp['dhash_detail'] ?? null,
                                'ahash' => $fp['ahash'],
                                'center_dhash' => $fp['center_dhash'] ?? null,
                                'color_signature' => $fp['color_signature'],
                                'aspect_ratio' => $fp['aspect_ratio'],
                                'search_text' => $variantSearchText,
                            ]);
                            $indexedPaths[$vImg] = true;
                            $indexedCount++;
                        }
                    }
                }
            }
        }

        return $indexedCount;
    }

    /**
     * Index all active products in the store.
     */
    public static function indexAllProducts(): int
    {
        $products = Product::where('is_active', true)->whereNotNull('featured_image')->get();
        $totalIndexed = 0;
        foreach ($products as $p) {
            $totalIndexed += self::indexProduct($p);
        }
        return $totalIndexed;
    }

    /**
     * Resolve on-disk storage path for a product image.
     */
    public static function resolveProductImagePath(string $path): ?string
    {
        // 1. Direct public storage path
        $publicStorage = public_path('storage/' . ltrim($path, '/'));
        if (file_exists($publicStorage)) {
            return $publicStorage;
        }

        // 2. Storage app/public path
        $appPublic = storage_path('app/public/' . ltrim($path, '/'));
        if (file_exists($appPublic)) {
            return $appPublic;
        }

        // 3. Raw public path
        $rawPublic = public_path(ltrim($path, '/'));
        if (file_exists($rawPublic)) {
            return $rawPublic;
        }

        return null;
    }

    /**
     * Helper to load GD image resource from file.
     */
    protected static function loadImageResource(string $filePath)
    {
        try {
            $info = @getimagesize($filePath);
            if (!$info) {
                return null;
            }

            $mime = $info['mime'] ?? '';
            switch ($mime) {
                case 'image/jpeg':
                case 'image/pjpeg':
                    return @imagecreatefromjpeg($filePath);
                case 'image/png':
                case 'image/x-png':
                    return @imagecreatefrompng($filePath);
                case 'image/webp':
                    return @imagecreatefromwebp($filePath);
                case 'image/gif':
                    return @imagecreatefromgif($filePath);
                default:
                    return null;
            }
        } catch (Throwable $e) {
            return null;
        }
    }
}

