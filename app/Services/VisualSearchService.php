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

        // 2. Calculate 64-bit Average Hash (aHash) - 8x8 grayscale
        $ahash = self::computeAHash($image, $origW, $origH);

        // 3. Calculate 52-bin Multi-zone Color Signature (Weighted center focus)
        $colorSignature = self::computeColorSignature($image, $origW, $origH);

        imagedestroy($image);

        return [
            'dhash' => $dhash,
            'ahash' => $ahash,
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
     * Compare query fingerprint against an indexed product image.
     * Returns a composite score from 0.0 to 100.0%.
     */
    public static function calculateMatchScore(array $queryFp, ProductVisualIndex $index): float
    {
        // 1. dHash match (difference gradients)
        $dDist = self::computeHammingDistance($queryFp['dhash'], $index->dhash);
        // Max distance is 64; <= 8 is near identical, <= 20 is strongly similar
        $dScore = max(0.0, (1.0 - ($dDist / 32.0)));

        // 2. aHash match (overall light/dark structure)
        $aDist = self::computeHammingDistance($queryFp['ahash'], $index->ahash);
        $aScore = max(0.0, (1.0 - ($aDist / 32.0)));

        // 3. Color signature match
        $cScore = 0.0;
        if (!empty($queryFp['color_signature']) && !empty($index->color_signature)) {
            $cScore = self::computeColorSimilarity($queryFp['color_signature'], $index->color_signature);
        }

        // Composite match score:
        // 45% dHash (structure) + 20% aHash (layout) + 35% Color (palette & subject tone)
        $composite = ($dScore * 0.45) + ($aScore * 0.20) + ($cScore * 0.35);

        // Aspect ratio boost/penalty: if ratios are very close, slight confidence boost
        $queryRatio = $queryFp['aspect_ratio'] ?? 1.0;
        $targetRatio = $index->aspect_ratio ?? 1.0;
        $ratioDelta = abs($queryRatio - $targetRatio);
        if ($ratioDelta <= 0.15) {
            $composite = min(1.0, $composite * 1.05);
        }

        return round($composite * 100.0, 1);
    }

    /**
     * Search product catalog using an uploaded image.
     */
    public static function searchByImage(string $uploadedTempPath, int $limit = 12, float $minScore = 35.0): array
    {
        $queryFp = self::createFingerprintFromPath($uploadedTempPath);
        if (!$queryFp) {
            return [];
        }

        // Fetch all visual indexes with active products
        $indexes = ProductVisualIndex::with('product')
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->get();

        $productMatches = [];

        foreach ($indexes as $index) {
            $product = $index->product;
            if (!$product) continue;

            $score = self::calculateMatchScore($queryFp, $index);

            if ($score >= $minScore) {
                $productId = $product->id;
                // Keep the best matching image score for each product
                if (!isset($productMatches[$productId]) || $score > $productMatches[$productId]['score']) {
                    $productMatches[$productId] = [
                        'product' => $product,
                        'score' => $score,
                        'matched_image' => $index->image_path,
                    ];
                }
            }
        }

        // Sort desc by similarity score
        uasort($productMatches, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_values($productMatches), 0, $limit);
    }

    /**
     * Index a single product's featured and gallery images.
     */
    public static function indexProduct(Product $product): int
    {
        // Remove existing indexes for this product to keep index clean
        ProductVisualIndex::where('product_id', $product->id)->delete();

        $indexedCount = 0;

        // 1. Index Featured Image
        if (!empty($product->featured_image)) {
            $fullPath = self::resolveProductImagePath($product->featured_image);
            if ($fullPath && file_exists($fullPath)) {
                $fp = self::createFingerprintFromPath($fullPath);
                if ($fp) {
                    ProductVisualIndex::create([
                        'product_id' => $product->id,
                        'image_path' => $product->featured_image,
                        'is_featured' => true,
                        'dhash' => $fp['dhash'],
                        'ahash' => $fp['ahash'],
                        'color_signature' => $fp['color_signature'],
                        'aspect_ratio' => $fp['aspect_ratio'],
                    ]);
                    $indexedCount++;
                }
            }
        }

        // 2. Index Gallery Images (if available)
        $gallery = $product->gallery;
        if (is_array($gallery)) {
            foreach ($gallery as $gImg) {
                if (empty($gImg) || $gImg === $product->featured_image) continue;

                $fullPath = self::resolveProductImagePath($gImg);
                if ($fullPath && file_exists($fullPath)) {
                    $fp = self::createFingerprintFromPath($fullPath);
                    if ($fp) {
                        ProductVisualIndex::create([
                            'product_id' => $product->id,
                            'image_path' => $gImg,
                            'is_featured' => false,
                            'dhash' => $fp['dhash'],
                            'ahash' => $fp['ahash'],
                            'color_signature' => $fp['color_signature'],
                            'aspect_ratio' => $fp['aspect_ratio'],
                        ]);
                        $indexedCount++;
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

