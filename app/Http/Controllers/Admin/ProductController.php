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

        // Product Offer Configuration
        $isOfferActive = $request->boolean('is_offer_active');
        $offerTitle = $request->filled('offer_title') ? trim($request->offer_title) : null;
        $offerDiscountType = $request->input('offer_discount_type', 'percentage');
        $offerDiscountValue = $request->filled('offer_discount_value') ? (float) $request->offer_discount_value : null;
        $offerStartDate = $request->filled('offer_start_date') ? $request->offer_start_date : null;
        $offerEndDate = $request->filled('offer_end_date') ? $request->offer_end_date : null;
        $hasValidOffer = $isOfferActive && ($offerDiscountValue !== null && $offerDiscountValue > 0);

        $initialBasePrice = (float) ($request->regular_price ?? 0);
        $initialOfferRegularPrice = null;

        if ($productType === 'simple' && $hasValidOffer) {
            $initialOfferRegularPrice = $initialBasePrice;
            if ($offerDiscountType === 'percentage') {
                $discount = ($initialBasePrice * $offerDiscountValue) / 100;
                $initialBasePrice = max(1.0, round($initialBasePrice - $discount));
            } elseif ($offerDiscountType === 'fixed') {
                $initialBasePrice = max(1.0, round($initialBasePrice - $offerDiscountValue));
            } elseif ($offerDiscountType === 'fixed_price') {
                $initialBasePrice = max(1.0, round($offerDiscountValue));
            }
        }

        $product = \App\Models\Product\Product::create([
            'title' => $request->name,
            'slug' => $slug,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'short_description' => $request->short_description,
            'description' => $request->description,
            'featured_image' => $thumbnailPath,
            'gallery' => $galleryPaths,
            'base_price' => $initialBasePrice,
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
            'is_offer_active' => $hasValidOffer,
            'offer_title' => $offerTitle,
            'offer_discount_type' => $offerDiscountType,
            'offer_discount_value' => $offerDiscountValue,
            'offer_regular_price' => $initialOfferRegularPrice,
            'offer_start_date' => $offerStartDate,
            'offer_end_date' => $offerEndDate,
            'offer_variant_prices' => null,
        ]);

        if ($productType === 'simple') {
            $sku = $request->filled('sku') ? trim($request->sku) : $this->generateNumericSku();
            $simpleOriginalPrice = $hasValidOffer ? $initialOfferRegularPrice : ($request->old_price ?? 0);
            $simpleCurrentPrice = $initialBasePrice;
            $simpleSalePrice = $hasValidOffer ? $initialBasePrice : ($request->old_price ? $request->regular_price : null);

            \App\Models\Product\ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'original_price' => $simpleOriginalPrice,
                'sale_price' => $simpleSalePrice,
                'current_price' => $simpleCurrentPrice,
                'stock_quantity' => $request->stock_quantity ?? 0,
                'attributes' => [],
            ]);
        } else {
            // Variable product handling
            if ($request->has('variants')) {
                $minPrice = null;
                $totalStock = 0;
                $offerVariantPrices = [];

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

                    $rawPrice = (float) ($variantData['price'] ?? 0);
                    $rawOldPrice = isset($variantData['old_price']) ? (float) $variantData['old_price'] : 0;
                    $stock = (int) ($variantData['stock'] ?? 0);
                    $totalStock += $stock;

                    if ($hasValidOffer) {
                        if ($offerDiscountType === 'percentage') {
                            $discount = ($rawPrice * $offerDiscountValue) / 100;
                            $vSellingPrice = max(1.0, round($rawPrice - $discount));
                        } elseif ($offerDiscountType === 'fixed') {
                            $vSellingPrice = max(1.0, round($rawPrice - $offerDiscountValue));
                        } elseif ($offerDiscountType === 'fixed_price') {
                            $vSellingPrice = max(1.0, round($offerDiscountValue));
                        } else {
                            $vSellingPrice = $rawPrice;
                        }

                        $vCurrentPrice = $vSellingPrice;
                        $vOriginalPrice = $rawOldPrice > 0 ? $rawOldPrice : $rawPrice;
                        $vSalePrice = $vSellingPrice;
                    } else {
                        $vCurrentPrice = $rawPrice;
                        $vOriginalPrice = $rawOldPrice;
                        $vSalePrice = $rawOldPrice > 0 ? $rawPrice : null;
                    }

                    if ($minPrice === null || $vCurrentPrice < $minPrice) {
                        $minPrice = $vCurrentPrice;
                    }

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

                    $createdVariant = \App\Models\Product\ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variantSku,
                        'original_price' => $vOriginalPrice,
                        'sale_price' => $vSalePrice,
                        'current_price' => $vCurrentPrice,
                        'stock_quantity' => $stock,
                        'images' => empty($variantImagePaths) ? null : $variantImagePaths,
                        'attributes' => $variantAttributes,
                    ]);

                    if ($hasValidOffer) {
                        $offerVariantPrices[$createdVariant->id] = $rawPrice;
                    }
                }

                $product->update([
                    'base_price' => $minPrice ?? 0,
                    'total_stock' => $totalStock,
                    'offer_regular_price' => $hasValidOffer ? $minPrice : null,
                    'offer_variant_prices' => $hasValidOffer ? $offerVariantPrices : null,
                ]);
            }
        }

        try {
            \App\Services\VisualSearchService::indexProduct($product);
        } catch (\Throwable $e) {}

        $this->clearProductCaches($product);

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

        // Product Offer Configuration
        $isOfferActive = $request->boolean('is_offer_active');
        $offerTitle = $request->filled('offer_title') ? trim($request->offer_title) : null;
        $offerDiscountType = $request->input('offer_discount_type', 'percentage');
        $offerDiscountValue = $request->filled('offer_discount_value') ? (float) $request->offer_discount_value : null;
        $offerStartDate = $request->filled('offer_start_date') ? $request->offer_start_date : null;
        $offerEndDate = $request->filled('offer_end_date') ? $request->offer_end_date : null;
        $hasValidOffer = $isOfferActive && ($offerDiscountValue !== null && $offerDiscountValue > 0);

        if ($productType === 'simple') {
            $baseRegularPrice = (float) ($request->regular_price ?? 0);
            if ($baseRegularPrice <= 0 && $product->offer_regular_price > 0) {
                $baseRegularPrice = (float) $product->offer_regular_price;
            } elseif ($baseRegularPrice <= 0) {
                $baseRegularPrice = (float) $product->base_price;
            }

            $oldPrice = $request->filled('old_price') ? (float) $request->old_price : 0;

            if ($hasValidOffer) {
                if ($offerDiscountType === 'percentage') {
                    $discount = ($baseRegularPrice * $offerDiscountValue) / 100;
                    $sellingPrice = max(1.0, round($baseRegularPrice - $discount));
                } elseif ($offerDiscountType === 'fixed') {
                    $sellingPrice = max(1.0, round($baseRegularPrice - $offerDiscountValue));
                } elseif ($offerDiscountType === 'fixed_price') {
                    $sellingPrice = max(1.0, round($offerDiscountValue));
                } else {
                    $sellingPrice = $baseRegularPrice;
                }

                $productBasePrice = $sellingPrice;
                $variantOriginalPrice = $oldPrice > 0 ? $oldPrice : $baseRegularPrice; // previous price preserved as crossed-out original price
                $variantCurrentPrice = $sellingPrice;
                $variantSalePrice = $sellingPrice;
                $offerRegularPriceToSave = $baseRegularPrice;
            } else {
                $productBasePrice = $baseRegularPrice;
                $variantOriginalPrice = $oldPrice;
                $variantCurrentPrice = $baseRegularPrice;
                $variantSalePrice = $oldPrice > 0 ? $baseRegularPrice : null;
                $offerRegularPriceToSave = null;
            }

            $product->update([
                'title' => $request->name,
                'slug' => $slug,
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'featured_image' => $thumbnailPath,
                'gallery' => $galleryPaths,
                'base_price' => $productBasePrice,
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
                // Offer fields
                'is_offer_active' => $hasValidOffer,
                'offer_title' => $offerTitle,
                'offer_discount_type' => $offerDiscountType,
                'offer_discount_value' => $offerDiscountValue,
                'offer_regular_price' => $offerRegularPriceToSave,
                'offer_start_date' => $offerStartDate,
                'offer_end_date' => $offerEndDate,
                'offer_variant_prices' => null,
            ]);

            $variant = $product->variants()->first();
            $sku = $request->filled('sku') ? trim($request->sku) : ($variant?->sku ?: $this->generateNumericSku());

            if ($variant) {
                $variant->update([
                    'sku' => $sku,
                    'original_price' => $variantOriginalPrice,
                    'sale_price' => $variantSalePrice,
                    'current_price' => $variantCurrentPrice,
                    'stock_quantity' => $request->stock_quantity ?? 0,
                ]);
            } else {
                $product->variants()->create([
                    'sku' => $sku,
                    'original_price' => $variantOriginalPrice,
                    'sale_price' => $variantSalePrice,
                    'current_price' => $variantCurrentPrice,
                    'stock_quantity' => $request->stock_quantity ?? 0,
                    'attributes' => [],
                ]);
            }
        } else {
            // Update variants
            $existingVariantIds = [];
            $minPrice = null;
            $totalStock = 0;
            $offerVariantPrices = [];

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

                    $rawPrice = (float) ($variantData['price'] ?? 0);
                    $rawOldPrice = isset($variantData['old_price']) ? (float) $variantData['old_price'] : 0;
                    $stock = (int) ($variantData['stock'] ?? 0);
                    $totalStock += $stock;

                    if ($hasValidOffer) {
                        if ($offerDiscountType === 'percentage') {
                            $discount = ($rawPrice * $offerDiscountValue) / 100;
                            $vSellingPrice = max(1.0, round($rawPrice - $discount));
                        } elseif ($offerDiscountType === 'fixed') {
                            $vSellingPrice = max(1.0, round($rawPrice - $offerDiscountValue));
                        } elseif ($offerDiscountType === 'fixed_price') {
                            $vSellingPrice = max(1.0, round($offerDiscountValue));
                        } else {
                            $vSellingPrice = $rawPrice;
                        }

                        $vCurrentPrice = $vSellingPrice;
                        $vOriginalPrice = $rawOldPrice > 0 ? $rawOldPrice : $rawPrice;
                        $vSalePrice = $vSellingPrice;
                    } else {
                        $vCurrentPrice = $rawPrice;
                        $vOriginalPrice = $rawOldPrice;
                        $vSalePrice = $rawOldPrice > 0 ? $rawPrice : null;
                    }

                    if ($minPrice === null || $vCurrentPrice < $minPrice) {
                        $minPrice = $vCurrentPrice;
                    }

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
                                'original_price' => $vOriginalPrice,
                                'sale_price' => $vSalePrice,
                                'current_price' => $vCurrentPrice,
                                'stock_quantity' => $stock,
                                'attributes' => $variantAttributes,
                            ];
                            if ($variantImages) {
                                $updateData['images'] = $variantImages;
                            }
                            $variant->update($updateData);
                            $existingVariantIds[] = $variant->id;
                            if ($hasValidOffer) {
                                $offerVariantPrices[$variant->id] = $rawPrice;
                            }
                        }
                    } else {
                        // Create new variant
                        $variantSku = !empty($variantData['sku']) ? trim($variantData['sku']) : $this->generateNumericSku();
                        $newVariant = \App\Models\Product\ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => $variantSku,
                            'original_price' => $vOriginalPrice,
                            'sale_price' => $vSalePrice,
                            'current_price' => $vCurrentPrice,
                            'stock_quantity' => $stock,
                            'images' => $variantImages,
                            'attributes' => $variantAttributes,
                        ]);
                        $existingVariantIds[] = $newVariant->id;
                        if ($hasValidOffer) {
                            $offerVariantPrices[$newVariant->id] = $rawPrice;
                        }
                    }
                }
            }
            
            // Delete removed variants
            $product->variants()->whereNotIn('id', $existingVariantIds)->delete();

            // Update main product with calculated price, stock, and offer
            $product->update([
                'title' => $request->name,
                'slug' => $slug,
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'featured_image' => $thumbnailPath,
                'gallery' => $galleryPaths,
                'base_price' => $minPrice ?? 0,
                'total_stock' => $totalStock,
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
                // Offer fields
                'is_offer_active' => $hasValidOffer,
                'offer_title' => $offerTitle,
                'offer_discount_type' => $offerDiscountType,
                'offer_discount_value' => $offerDiscountValue,
                'offer_regular_price' => $hasValidOffer ? ($product->offer_regular_price ?: $minPrice) : null,
                'offer_start_date' => $offerStartDate,
                'offer_end_date' => $offerEndDate,
                'offer_variant_prices' => $hasValidOffer ? $offerVariantPrices : null,
            ]);
        }

        try {
            \App\Services\VisualSearchService::indexProduct($product);
        } catch (\Throwable $e) {}

        $this->clearProductCaches($product);

        return redirect()->back()->with('success', 'Product updated successfully!');
    }

    public function destroy(\App\Models\Product\Product $product)
    {
        $this->clearProductCaches($product);
        $product->delete();
        return redirect()->back()->with('success', 'Product deleted successfully!');
    }

    protected function clearProductCaches(?\App\Models\Product\Product $product = null): void
    {
        \Illuminate\Support\Facades\Cache::forget('homepage_trending');
        \Illuminate\Support\Facades\Cache::forget('homepage_sections');
        if ($product && $product->category_id) {
            \Illuminate\Support\Facades\Cache::forget('category_pool_' . $product->category_id);
        }
    }

    public function reindexVisual()
    {
        $count = \App\Services\VisualSearchService::indexAllProducts();
        return redirect()->back()->with('success', "Visual search index refreshed successfully! Total {$count} images indexed.");
    }

    public function syncVisual(\App\Models\Product\Product $product)
    {
        $count = \App\Services\VisualSearchService::indexProduct($product);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Visual index synced for \"{$product->title}\"! Total {$count} image(s) indexed.",
                'count' => $count,
            ]);
        }

        return redirect()->back()->with('success', "Visual index synced for \"{$product->title}\"! Total {$count} image(s) indexed (featured, gallery & variants).");
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
