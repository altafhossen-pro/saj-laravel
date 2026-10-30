<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Product\Product::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $perPage = $request->input('per_page', 10);
        $products = $query->latest()->paginate($perPage)->withQueryString();

        $categories = \App\Models\Category::all();

        // If it's an AJAX request (for onchange search), return only the table partial
        if ($request->ajax()) {
            return view('admin.products.partials.table', compact('products'))->render();
        }

        return view('admin.products.index', compact('products', 'categories', 'perPage'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $products = \App\Models\Product\Product::select('id', 'title', 'product_type', 'featured_image', 'base_price', 'total_stock')
            ->with(['variants' => function($q) {
                $q->select('id', 'product_id', 'sku', 'attributes', 'current_price', 'images', 'stock_quantity');
            }])
            ->where('title', 'like', "%{$query}%")
            ->orWhereHas('variants', function($q) use ($query) {
                $q->where('sku', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($product) {
                $firstVariant = $product->variants->first();
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sku' => $firstVariant ? $firstVariant->sku : null,
                    'product_type' => $product->product_type,
                    'featured_image' => $product->featured_image,
                    'current_price' => $product->base_price, // map to expected field
                    'stock' => $product->total_stock ?? 0,
                    'variants' => $product->variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'attributes' => $variant->attributes,
                            'current_price' => $variant->current_price,
                            'stock' => $variant->stock_quantity ?? 0,
                            'images' => $variant->images ? [$variant->images[0] ?? null] : [], // Only need first image
                        ];
                    }),
                ];
            });
            
        return response()->json($products);
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'regular_price' => 'required_if:product_type,single|numeric|nullable',
            'sku' => 'nullable|string|max:100|unique:product_variants,sku',
            'old_price' => 'nullable|numeric',
            'thumbnail' => 'required|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        $slug = $request->slug ? \Illuminate\Support\Str::slug($request->slug) : \Illuminate\Support\Str::slug($request->name);
        
        // Handle Thumbnail (Optimized only if Convert & Optimize toggle is ON)
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            if ($request->has('auto_convert_thumbnail')) {
                $thumbnailPath = \App\Services\ImageOptimizerService::uploadAndOptimize($file, 'products', 800, 800, 80);
            } else {
                $thumbnailPath = $file->store('products', 'public');
            }
        }

        // Handle Gallery (Optimized only if Convert & Optimize toggle is ON)
        $galleryPaths = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                if ($request->has('auto_convert_gallery')) {
                    $galleryPaths[] = \App\Services\ImageOptimizerService::uploadAndOptimize($file, 'products/gallery', 1000, 1000, 80);
                } else {
                    $galleryPaths[] = $file->store('products/gallery', 'public');
                }
            }
        }

        $productType = $request->product_type === 'variable' ? 'variable' : 'simple';

        $product = \App\Models\Product\Product::create([
            'title' => $request->name,
            'slug' => $slug,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'short_description' => $request->short_description,
            'description' => $request->description,
            'featured_image' => $thumbnailPath,
            'gallery' => $galleryPaths,
            'base_price' => $request->regular_price ?? 0,
            'total_stock' => $request->stock_quantity ?? 0,
            'is_active' => $request->has('is_active'),
            'is_featured' => $request->has('is_featured'),
            'is_trending' => $request->has('is_trending'),
            'is_bestselling' => $request->has('is_bestselling'),
            'is_new_arrival' => $request->has('is_new_arrival'),
            'is_force_out_of_stock' => $request->has('is_force_out_of_stock'),
            'exclude_from_category_discount' => $request->has('exclude_from_category_discount'),
            'announcement_text' => $request->announcement_text,
            'delivery_info' => $request->shipping_info, // Changed field name in view
            'additional_info' => $request->additional_info,
            'warranty_info' => $request->warranty_info,
            'seo' => [
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ],
            'product_type' => $productType,
            'use_variant_images' => $request->has('use_variant_images'),
            'tags' => $request->tags ?? [],
            'metrics_mode' => $request->metrics_mode ?? 'organic',
            'metrics_start_date' => $request->metrics_start_date,
            'metrics_daily_avg' => $request->metrics_daily_avg,
            'rating_metrics_mode' => $request->rating_metrics_mode ?? 'manual',
            'rating_metrics_start_date' => $request->rating_metrics_start_date,
            'rating_metrics_interval_days' => $request->rating_metrics_interval_days ?? 7,
            'rating_metrics_count' => $request->rating_metrics_count ?? 1,
            'rating_metrics_min' => $request->rating_metrics_min ?? 4.5,
            'rating_metrics_max' => $request->rating_metrics_max ?? 5.0,
        ]);

        if ($productType === 'simple') {
            $sku = $request->filled('sku') ? trim($request->sku) : $this->generateNumericSku();
            \App\Models\Product\ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'original_price' => $request->old_price ?? 0,
                'sale_price' => $request->old_price ? $request->regular_price : null,
                'current_price' => $request->regular_price ?? 0,
                'stock_quantity' => $request->stock_quantity ?? 0,
                'attributes' => [],
            ]);
        } else {
            // Variable product handling
            if ($request->has('variants')) {
                $minPrice = null;
                $totalStock = 0;

                foreach ($request->variants as $variantData) {
                    $variantImagePaths = [];
                    if (isset($variantData['image'])) {
                        if ($request->has('auto_convert_variants')) {
                            $variantImagePaths[] = \App\Services\ImageOptimizerService::uploadAndOptimize(
                                $variantData['image'],
                                'products/variants',
                                800,
                                800,
                                80
                            );
                        } else {
                            $variantImagePaths[] = $variantData['image']->store('products/variants', 'public');
                        }
                    }

                    $currentPrice = $variantData['price'] ?? 0;
                    $stock = $variantData['stock'] ?? 0;

                    if ($minPrice === null || $currentPrice < $minPrice) {
                        $minPrice = $currentPrice;
                    }
                    $totalStock += $stock;
                    // Parse attributes
                    $variantAttributes = [];
                    if (!empty($variantData['attributes']) && is_array($variantData['attributes'])) {
                        foreach ($variantData['attributes'] as $attr) {
                            if (!empty($attr['type']) && !empty($attr['value'])) {
                                $variantAttributes[$attr['type']] = $attr['value'];
                            }
                        }
                    }

                    $variantSku = !empty($variantData['sku']) ? trim($variantData['sku']) : $this->generateNumericSku();

                    \App\Models\Product\ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variantSku,
                        'original_price' => $variantData['old_price'] ?? 0,
                        'current_price' => $currentPrice,
                        'stock_quantity' => $stock,
                        'images' => empty($variantImagePaths) ? null : $variantImagePaths,
                        'attributes' => $variantAttributes,
                    ]);
                }

                $product->update([
                    'base_price' => $minPrice ?? 0,
                    'total_stock' => $totalStock,
                ]);
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Product saved successfully!');
    }

    public function edit(\App\Models\Product\Product $product)
    {
        $categories = \App\Models\Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, \App\Models\Product\Product $product)
    {
        // Handle Thumbnail Update (Optimized only if Convert & Optimize toggle is ON)
        $thumbnailPath = $product->featured_image;
        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            if ($request->has('auto_convert_thumbnail')) {
                $thumbnailPath = \App\Services\ImageOptimizerService::uploadAndOptimize($file, 'products', 800, 800, 80);
            } else {
                $thumbnailPath = $file->store('products', 'public');
            }
        }

        // Handle Gallery Update (Optimized only if Convert & Optimize toggle is ON)
        $galleryPaths = $product->gallery ?? [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                if ($request->has('auto_convert_gallery')) {
                    $galleryPaths[] = \App\Services\ImageOptimizerService::uploadAndOptimize($file, 'products/gallery', 1000, 1000, 80);
                } else {
                    $galleryPaths[] = $file->store('products/gallery', 'public');
                }
            }
        }

        $productType = $request->product_type === 'variable' ? 'variable' : 'simple';

        if ($productType === 'simple' && $request->filled('sku')) {
            $existingVariant = $product->variants()->first();
            $request->validate([
                'sku' => [
                    'nullable',
                    'string',
                    'max:100',
                    \Illuminate\Validation\Rule::unique('product_variants', 'sku')->ignore($existingVariant?->id),
                ],
            ]);
        }

        $slug = $request->slug ? \Illuminate\Support\Str::slug($request->slug) : \Illuminate\Support\Str::slug($request->name);

        $product->update([
            'title' => $request->name,
            'slug' => $slug,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'short_description' => $request->short_description,
            'description' => $request->description,
            'featured_image' => $thumbnailPath,
            'gallery' => $galleryPaths,
            'base_price' => $request->regular_price ?? 0,
            'total_stock' => $request->stock_quantity ?? 0,
            'is_active' => $request->has('is_active'),
            'is_featured' => $request->has('is_featured'),
            'is_trending' => $request->has('is_trending'),
            'is_bestselling' => $request->has('is_bestselling'),
            'is_new_arrival' => $request->has('is_new_arrival'),
            'is_force_out_of_stock' => $request->has('is_force_out_of_stock'),
            'exclude_from_category_discount' => $request->has('exclude_from_category_discount'),
            'announcement_text' => $request->announcement_text,
            'delivery_info' => $request->shipping_info,
            'additional_info' => $request->additional_info,
            'warranty_info' => $request->warranty_info,
            'seo' => [
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ],
            'product_type' => $productType,
            'use_variant_images' => $request->has('use_variant_images'),
            'tags' => $request->tags ?? [],
            'metrics_mode' => $request->metrics_mode ?? 'organic',
            'rating_metrics_mode' => $request->rating_metrics_mode ?? 'manual',
            'rating_metrics_start_date' => $request->rating_metrics_start_date,
            'rating_metrics_interval_days' => $request->rating_metrics_interval_days ?? 7,
            'rating_metrics_count' => $request->rating_metrics_count ?? 1,
            'rating_metrics_min' => $request->rating_metrics_min ?? 4.5,
            'rating_metrics_max' => $request->rating_metrics_max ?? 5.0,
            'metrics_start_date' => $request->metrics_start_date,
            'metrics_daily_avg' => $request->metrics_daily_avg,
        ]);

        if ($productType === 'simple') {
            $variant = $product->variants()->first();
            $sku = $request->filled('sku') ? trim($request->sku) : ($variant?->sku ?: $this->generateNumericSku());

            if ($variant) {
                $variant->update([
                    'sku' => $sku,
                    'original_price' => $request->old_price ?? 0,
                    'sale_price' => $request->old_price ? $request->regular_price : null,
                    'current_price' => $request->regular_price ?? 0,
                    'stock_quantity' => $request->stock_quantity ?? 0,
                ]);
            } else {
                $product->variants()->create([
                    'sku' => $sku,
                    'original_price' => $request->old_price ?? 0,
                    'sale_price' => $request->old_price ? $request->regular_price : null,
                    'current_price' => $request->regular_price ?? 0,
                    'stock_quantity' => $request->stock_quantity ?? 0,
                    'attributes' => [],
                ]);
            }
        } else {
            // Update variants
            $existingVariantIds = [];
            $minPrice = null;
            $totalStock = 0;

            if ($request->has('variants')) {
                foreach ($request->variants as $variantData) {
                    $variantImages = null;
                    if (isset($variantData['image'])) {
                        if ($request->has('auto_convert_variants')) {
                            $variantImages = [\App\Services\ImageOptimizerService::uploadAndOptimize(
                                $variantData['image'],
                                'products/variants',
                                800,
                                800,
                                80
                            )];
                        } else {
                            $variantImages = [$variantData['image']->store('products/variants', 'public')];
                        }
                    }

                    $currentPrice = $variantData['price'] ?? 0;
                    $stock = $variantData['stock'] ?? 0;

                    if ($minPrice === null || $currentPrice < $minPrice) {
                        $minPrice = $currentPrice;
                    }
                    $totalStock += $stock;

                    // Parse attributes
                    $variantAttributes = [];
                    if (!empty($variantData['attributes']) && is_array($variantData['attributes'])) {
                        foreach ($variantData['attributes'] as $attr) {
                            if (!empty($attr['type']) && !empty($attr['value'])) {
                                $variantAttributes[$attr['type']] = $attr['value'];
                            }
                        }
                    }

                    if (!empty($variantData['id'])) {
                        // Update existing variant
                        $variant = \App\Models\Product\ProductVariant::find($variantData['id']);
                        if ($variant) {
                            $variantSku = !empty($variantData['sku']) ? trim($variantData['sku']) : ($variant->sku ?: $this->generateNumericSku());
                            $updateData = [
                                'sku' => $variantSku,
                                'original_price' => $variantData['old_price'] ?? 0,
                                'current_price' => $currentPrice,
                                'stock_quantity' => $stock,
                                'attributes' => $variantAttributes,
                            ];
                            if ($variantImages) {
                                $updateData['images'] = $variantImages;
                            }
                            $variant->update($updateData);
                            $existingVariantIds[] = $variant->id;
                        }
                    } else {
                        // Create new variant
                        $variantSku = !empty($variantData['sku']) ? trim($variantData['sku']) : $this->generateNumericSku();
                        $newVariant = \App\Models\Product\ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => $variantSku,
                            'original_price' => $variantData['old_price'] ?? 0,
                            'current_price' => $currentPrice,
                            'stock_quantity' => $stock,
                            'images' => $variantImages,
                            'attributes' => $variantAttributes,
                        ]);
                        $existingVariantIds[] = $newVariant->id;
                    }
                }
            }
            
            // Delete removed variants
            $product->variants()->whereNotIn('id', $existingVariantIds)->delete();

            // Update main product with calculated price and stock
            $product->update([
                'base_price' => $minPrice ?? 0,
                'total_stock' => $totalStock,
            ]);
        }

        return redirect()->back()->with('success', 'Product updated successfully!');
    }

    public function destroy(\App\Models\Product\Product $product)
    {
        $product->delete();
        return redirect()->back()->with('success', 'Product deleted successfully!');
    }

    public function checkSlug(Request $request)
    {
        $slug = \Illuminate\Support\Str::slug($request->slug);
        $productId = $request->product_id;

        $exists = \App\Models\Product\Product::where('slug', $slug)
            ->when($productId, function($query) use ($productId) {
                return $query->where('id', '!=', $productId);
            })->exists();

        return response()->json([
            'is_available' => !$exists,
            'slug' => $slug
        ]);
    }

    /**
     * Generate an all-numeric, timestamp-based unique SKU.
     */
    private function generateNumericSku(): string
    {
        do {
            $sku = date('ymdHis') . mt_rand(100, 999);
        } while (\App\Models\Product\ProductVariant::where('sku', $sku)->exists());

        return $sku;
    }
}
