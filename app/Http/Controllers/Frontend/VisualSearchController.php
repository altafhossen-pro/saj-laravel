<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\VisualSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class VisualSearchController extends Controller
{
    /**
     * Handle reverse image search AJAX request from modal or search bar.
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|file|max:25600',
            'image_base64' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:40',
            'min_score' => 'nullable|numeric|min:10|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid image file (JPG, PNG, WebP) under 25MB.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tempPath = null;
        $shouldUnlink = false;

        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $tempPath = $request->file('image')->getRealPath();
                $imgInfo = @getimagesize($tempPath);
                if (!$imgInfo || !in_array($imgInfo['mime'] ?? '', ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/webp', 'image/gif'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The uploaded file does not appear to be a recognized image format (JPG, PNG, WebP).',
                    ], 422);
                }
            } elseif (!empty($request->input('image_base64'))) {
                $base64 = $request->input('image_base64');
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                    $base64 = substr($base64, strpos($base64, ',') + 1);
                }
                $data = base64_decode($base64);
                if (!$data) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid base64 image data provided.',
                    ], 422);
                }

                $tempPath = tempnam(sys_get_temp_dir(), 'vis_');
                file_put_contents($tempPath, $data);
                $shouldUnlink = true;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No image was uploaded. Please take a photo or select an image file.',
                ], 422);
            }

            $limit = (int) $request->input('limit', 24);
            $minScore = (float) $request->input('min_score', 45.0);

            $extractedText = $request->input('ocr_text', $request->input('text', null));
            if ($request->hasFile('image')) {
                $clientFilename = pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME);
                if (!empty($clientFilename) && !preg_match('/^(image|img|screenshot|photo|file|upload|vis_)[\d_\-]*$/i', $clientFilename)) {
                    $extractedText = trim(($extractedText ?? '') . ' ' . str_replace(['_', '-'], ' ', $clientFilename));
                }
            }

            $rawResults = VisualSearchService::searchByImage($tempPath, $limit, $minScore, $extractedText);

            // Persist search token and public thumbnail for full-page results (/search?visual=TOKEN)
            $token = Str::random(16);
            $publicImageUrl = self::storePublicTempThumbnail($tempPath, $token);

            $productScores = [];
            $formatted = [];

            foreach ($rawResults as $item) {
                $p = $item['product'];
                $score = round($item['score'], 0);
                $productScores[$p->id] = $score;

                $img = $p->featured_image;
                $imgUrl = null;
                if ($img) {
                    $imgUrl = str_starts_with($img, 'http') ? $img : asset('storage/' . ltrim($img, '/'));
                }

                $confidenceLabel = $score >= 80 ? 'Exact Match' : ($score >= 60 ? 'High Match' : 'Similar Style');

                $formatted[] = [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'url' => route('product.show', $p->slug),
                    'base_price' => (float) $p->base_price,
                    'price_range' => $p->price_range,
                    'featured_image' => $img,
                    'image_url' => $imgUrl,
                    'category' => $p->category?->name,
                    'score' => $score,
                    'score_label' => "{$score}% Match",
                    'confidence' => $confidenceLabel,
                ];
            }

            // Cache visual search session for 2 hours
            Cache::put('vis_search_' . $token, [
                'token' => $token,
                'image_url' => $publicImageUrl,
                'product_scores' => $productScores,
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(2));

            $pageUrl = route('shop.search', ['visual' => $token]);

            return response()->json([
                'success' => true,
                'count' => count($formatted),
                'token' => $token,
                'page_url' => $pageUrl,
                'results' => $formatted,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Visual search processing error: ' . $e->getMessage(),
            ], 500);
        } finally {
            if ($shouldUnlink && $tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Handle direct form upload POST /search/image and redirect to /search?visual=TOKEN
     */
    public function webUpload(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|max:25600',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->with('error', 'Please provide a valid image file under 25MB.');
        }

        $file = $request->file('image');
        $tempPath = $file->getRealPath();

        $imgInfo = @getimagesize($tempPath);
        if (!$imgInfo || !in_array($imgInfo['mime'] ?? '', ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/webp', 'image/gif'])) {
            return redirect()->back()->with('error', 'The uploaded file does not appear to be a recognized image format (JPG, PNG, WebP).');
        }

        $extractedText = $request->input('ocr_text', null);
        $clientFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        if (!empty($clientFilename) && !preg_match('/^(image|img|screenshot|photo|file|upload|vis_)[\d_\-]*$/i', $clientFilename)) {
            $extractedText = trim(($extractedText ?? '') . ' ' . str_replace(['_', '-'], ' ', $clientFilename));
        }

        $rawResults = VisualSearchService::searchByImage($tempPath, 30, 45.0, $extractedText);

        $token = Str::random(16);
        $publicImageUrl = self::storePublicTempThumbnail($tempPath, $token);

        $productScores = [];
        foreach ($rawResults as $item) {
            $productScores[$item['product']->id] = round($item['score'], 0);
        }

        Cache::put('vis_search_' . $token, [
            'token' => $token,
            'image_url' => $publicImageUrl,
            'product_scores' => $productScores,
            'created_at' => now()->toDateTimeString(),
        ], now()->addHours(2));

        return redirect()->route('shop.search', ['visual' => $token]);
    }

    /**
     * Store a lightweight temporary thumbnail in storage/app/public/temp_search
     */
    protected static function storePublicTempThumbnail(string $sourcePath, string $token): string
    {
        try {
            $dir = storage_path('app/public/temp_search');
            if (!file_exists($dir)) {
                @mkdir($dir, 0775, true);
            }

            // Stale thumbnail cleanup (> 4 hours old) to protect server disk space
            $oldFiles = @glob($dir . DIRECTORY_SEPARATOR . 'vis_*.jpg');
            if ($oldFiles && is_array($oldFiles)) {
                $staleTime = time() - 14400;
                foreach ($oldFiles as $oldF) {
                    if (@filemtime($oldF) < $staleTime) {
                        @unlink($oldF);
                    }
                }
            }

            $filename = "vis_{$token}.jpg";
            $dest = $dir . DIRECTORY_SEPARATOR . $filename;

            // Resize to max 300x300 thumbnail for ultra fast rendering
            $info = @getimagesize($sourcePath);
            if ($info) {
                $src = null;
                $mime = $info['mime'] ?? '';
                if ($mime === 'image/jpeg' || $mime === 'image/pjpeg') $src = @imagecreatefromjpeg($sourcePath);
                elseif ($mime === 'image/png') $src = @imagecreatefrompng($sourcePath);
                elseif ($mime === 'image/webp') $src = @imagecreatefromwebp($sourcePath);

                if ($src) {
                    $w = imagesx($src);
                    $h = imagesy($src);
                    $maxDim = 300;
                    $scale = min(1.0, $maxDim / max($w, $h));
                    $nw = max(1, (int) round($w * $scale));
                    $nh = max(1, (int) round($h * $scale));

                    $thumb = imagecreatetruecolor($nw, $nh);
                    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    imagejpeg($thumb, $dest, 85);
                    imagedestroy($thumb);
                    imagedestroy($src);

                    return asset('storage/temp_search/' . $filename);
                }
            }

            @copy($sourcePath, $dest);
            return asset('storage/temp_search/' . $filename);
        } catch (Throwable $e) {
            return '';
        }
    }
}
