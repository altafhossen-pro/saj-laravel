<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\VisualSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class VisualSearchController extends Controller
{
    /**
     * Handle reverse image search request from frontend.
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'image_base64' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:30',
            'min_score' => 'nullable|numeric|min:10|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid image file (JPG, PNG, WebP) under 10MB.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tempPath = null;
        $shouldUnlink = false;

        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $tempPath = $request->file('image')->getRealPath();
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

            $limit = (int) $request->input('limit', 12);
            $minScore = (float) $request->input('min_score', 35.0);

            $rawResults = VisualSearchService::searchByImage($tempPath, $limit, $minScore);

            $formatted = [];
            foreach ($rawResults as $item) {
                $p = $item['product'];
                $img = $p->featured_image;
                $imgUrl = null;
                if ($img) {
                    $imgUrl = str_starts_with($img, 'http') ? $img : asset('storage/' . ltrim($img, '/'));
                }

                $score = round($item['score'], 0);
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

            return response()->json([
                'success' => true,
                'count' => count($formatted),
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
}
