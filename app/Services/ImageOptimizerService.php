<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizerService
{
    /**
     * Optimizes, resizes, and converts an uploaded image to WebP format.
     * Fallback to original store if GD cannot process it.
     *
     * @param UploadedFile $file
     * @param string $directory (e.g. 'products', 'products/gallery', 'products/variants')
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality (1-100, default 80 for optimal compression/clarity)
     * @return string relative storage path
     */
    public static function uploadAndOptimize(
        UploadedFile $file,
        string $directory = 'products',
        int $maxWidth = 1000,
        int $maxHeight = 1000,
        int $quality = 80
    ): string {
        $realPath = $file->getRealPath();
        $mime = $file->getMimeType();

        // If not a standard raster image or GD doesn't support webp, store normally
        if (!function_exists('imagewebp') || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/bmp'])) {
            return $file->store($directory, 'public');
        }

        try {
            $imageInfo = @getimagesize($realPath);
            if (!$imageInfo) {
                return $file->store($directory, 'public');
            }

            $origWidth = $imageInfo[0];
            $origHeight = $imageInfo[1];

            // If user already uploaded a proper WebP image that isn't excessively oversized, preserve it untouched!
            if ($mime === 'image/webp' && $origWidth <= $maxWidth && $origHeight <= $maxHeight) {
                return $file->store($directory, 'public');
            }

            // Create image resource based on mime
            switch ($mime) {
                case 'image/jpeg':
                    $srcImg = @imagecreatefromjpeg($realPath);
                    break;
                case 'image/png':
                    $srcImg = @imagecreatefrompng($realPath);
                    break;
                case 'image/webp':
                    $srcImg = @imagecreatefromwebp($realPath);
                    break;
                default:
                    $srcImg = null;
            }

            if (!$srcImg) {
                return $file->store($directory, 'public');
            }

            // Calculate scaled dimensions keeping aspect ratio
            $targetWidth = $origWidth;
            $targetHeight = $origHeight;

            if ($origWidth > $maxWidth || $origHeight > $maxHeight) {
                $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
                $targetWidth = (int) round($origWidth * $ratio);
                $targetHeight = (int) round($origHeight * $ratio);
            }

            // Create truecolor canvas with transparency support
            $destImg = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($destImg, false);
            imagesavealpha($destImg, true);

            // Resample with high quality interpolation
            imagecopyresampled($destImg, $srcImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);

            // Generate clean unique filename with .webp
            $filename = Str::random(40) . '.webp';
            $relativeDir = trim($directory, '/');
            $fullDir = storage_path('app/public/' . $relativeDir);

            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }

            $destFilePath = $fullDir . '/' . $filename;

            // Save as WebP
            $success = imagewebp($destImg, $destFilePath, $quality);

            // Free memory
            imagedestroy($srcImg);
            imagedestroy($destImg);

            if ($success && file_exists($destFilePath)) {
                return $relativeDir . '/' . $filename;
            }
        } catch (\Throwable $e) {
            // Silently fallback to standard store on any processing exception
        }

        return $file->store($directory, 'public');
    }
}
