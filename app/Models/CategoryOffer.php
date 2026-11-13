<?php

namespace App\Models;

use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CategoryOffer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'include_subcategories' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price_backup' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get IDs of all categories targeted by this offer.
     */
    public function getTargetCategoryIds(): ?array
    {
        if (is_null($this->category_id)) {
            // Null means All Categories (bulk apply across entire store)
            return null;
        }

        if ($this->include_subcategories && $this->category) {
            return $this->category->getAllDescendantIds();
        }

        return [$this->category_id];
    }

    /**
     * Get query for all target products eligible for discount.
     */
    public function getTargetProductsQuery()
    {
        $query = Product::with('variants')
            ->where('exclude_from_category_discount', false);

        $categoryIds = $this->getTargetCategoryIds();
        if (!is_null($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        return $query;
    }

    /**
     * Apply the discount across target products, saving original prices into price_backup.
     */
    public function applyDiscounts(): int
    {
        return DB::transaction(function () {
            // If already applied previously, revert first so we don't double discount
            if (!empty($this->price_backup)) {
                $this->revertDiscountsInternal();
            }

            $products = $this->getTargetProductsQuery()->get();
            $backup = [
                'products' => [],
                'variants' => [],
            ];
            $affectedCount = 0;

            foreach ($products as $product) {
                $productChanged = false;
                $originalBasePrice = (float) $product->base_price;

                foreach ($product->variants as $variant) {
                    $curr = (float) $variant->current_price;
                    $orig = $variant->original_price !== null ? (float) $variant->original_price : null;

                    // Calculate discounted price
                    if ($this->discount_type === 'percentage') {
                        $discountAmount = ($curr * (float) $this->discount_value) / 100;
                        $newPrice = max(1, round($curr - $discountAmount));
                    } else {
                        $newPrice = max(1, round($curr - (float) $this->discount_value));
                    }

                    // Save snapshot in backup
                    $backup['variants'][$variant->id] = [
                        'current_price' => $curr,
                        'original_price' => $orig,
                    ];

                    // Auto update: old price becomes current regular price, new price becomes discounted
                    $variant->original_price = $curr;
                    $variant->current_price = $newPrice;
                    $variant->save();

                    $productChanged = true;
                }

                if ($productChanged) {
                    $backup['products'][$product->id] = [
                        'base_price' => $originalBasePrice,
                    ];

                    // Update product base_price (to match min variant current_price or discounted base_price)
                    $minVariantPrice = $product->variants()->min('current_price');
                    $product->base_price = $minVariantPrice ?? $product->base_price;
                    $product->save();

                    $affectedCount++;
                }
            }

            $this->price_backup = $backup;
            $this->products_affected_count = $affectedCount;
            $this->is_active = true;
            $this->save();

            return $affectedCount;
        });
    }

    /**
     * Revert all prices back to their original state from price_backup.
     */
    public function revertDiscounts(): int
    {
        return DB::transaction(function () {
            $count = $this->revertDiscountsInternal();
            $this->is_active = false;
            $this->products_affected_count = 0;
            $this->price_backup = null;
            $this->save();

            return $count;
        });
    }

    /**
     * Internal revert helper without modifying $this attributes directly.
     */
    protected function revertDiscountsInternal(): int
    {
        $backup = $this->price_backup;
        if (empty($backup)) {
            return 0;
        }

        $revertedCount = 0;

        if (!empty($backup['variants'])) {
            foreach ($backup['variants'] as $vId => $prices) {
                ProductVariant::where('id', $vId)->update([
                    'current_price' => $prices['current_price'],
                    'original_price' => $prices['original_price'],
                ]);
            }
        }

        if (!empty($backup['products'])) {
            foreach ($backup['products'] as $pId => $prices) {
                Product::where('id', $pId)->update([
                    'base_price' => $prices['base_price'],
                ]);
                $revertedCount++;
            }
        }

        return $revertedCount;
    }

    protected static function booted()
    {
        static::saving(function ($offer) {
            if (empty($offer->title)) {
                $categoryName = $offer->category ? $offer->category->name : 'Category';
                $offer->title = "{$categoryName} Discount";
            }
        });

        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('active_category_offers_list');
            \Illuminate\Support\Facades\Cache::forget('homepage_trending');
            \Illuminate\Support\Facades\Cache::forget('homepage_sections');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('active_category_offers_list');
            \Illuminate\Support\Facades\Cache::forget('homepage_trending');
            \Illuminate\Support\Facades\Cache::forget('homepage_sections');
        });
    }
}
