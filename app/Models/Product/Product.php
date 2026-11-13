<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'use_variant_images' => 'boolean',
        'is_global_subtitle_on' => 'boolean',
        'sub_categories' => 'array',
        'tags' => 'array',
        'exclude_from_category_discount' => 'boolean',
        'is_bracelet' => 'boolean',
        'is_ring' => 'boolean',
        'bracelet_sizes' => 'array',
        'ring_sizes' => 'array',
        'gallery' => 'array',
        'product_videos' => 'array',
        'price_range' => 'array',
        'specifications' => 'array',
        'available_attributes' => 'array',
        'is_active' => 'boolean',
        'is_force_out_of_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_bestselling' => 'boolean',
        'is_new_arrival' => 'boolean',
        'shipping_info' => 'array',
        'seo' => 'array',
        'rating_breakdown' => 'array',
        'base_price' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'metrics_start_date' => 'date',
        'is_offer_active' => 'boolean',
        'offer_discount_value' => 'decimal:2',
        'offer_regular_price' => 'decimal:2',
        'offer_start_date' => 'datetime',
        'offer_end_date' => 'datetime',
        'offer_variant_prices' => 'array',
    ];

    protected $appends = ['total_sold', 'original_price', 'total_reviews', 'active_discount_percent'];

    /**
     * Get the active category offer if applicable.
     */
    public function getActiveOfferAttribute(): ?\App\Models\CategoryOffer
    {
        if ($this->exclude_from_category_discount) {
            return null;
        }

        $activeOffers = \Illuminate\Support\Facades\Cache::remember('active_category_offers_list', 60, function () {
            return \App\Models\CategoryOffer::where('is_active', true)->get();
        });

        if ($activeOffers->isEmpty()) {
            return null;
        }

        // 1. Direct category match
        if ($this->category_id) {
            $direct = $activeOffers->firstWhere('category_id', $this->category_id);
            if ($direct) {
                return $direct;
            }
        }

        // 2. Store-wide offer (category_id is null)
        $storewide = $activeOffers->first(fn($o) => is_null($o->category_id));
        if ($storewide) {
            return $storewide;
        }

        // 3. Parent category offer if include_subcategories is true
        if ($this->category_id) {
            $cat = $this->relationLoaded('category') ? $this->category : $this->category()->first();
            if ($cat && $cat->parent_id) {
                $parentOffer = $activeOffers->firstWhere('category_id', $cat->parent_id);
                if ($parentOffer && $parentOffer->include_subcategories) {
                    return $parentOffer;
                }
            }
        }

        return null;
    }

    /**
     * Check if product-specific offer is currently active.
     */
    public function isProductOfferActive(): bool
    {
        if (!$this->is_offer_active) {
            return false;
        }

        $now = now();
        if ($this->offer_start_date && $now->lt($this->offer_start_date)) {
            return false;
        }
        if ($this->offer_end_date && $now->gt($this->offer_end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discounted offer price from a given regular price.
     */
    public function calculateDiscountedPrice(float $regularPrice): float
    {
        if ($regularPrice <= 0) {
            return 0.0;
        }

        $type = $this->offer_discount_type ?? 'percentage';
        $val = (float) ($this->offer_discount_value ?? 0);

        if ($val <= 0) {
            return $regularPrice;
        }

        if ($type === 'percentage') {
            $discount = ($regularPrice * $val) / 100;
            return max(1.0, round($regularPrice - $discount));
        }

        if ($type === 'fixed') {
            return max(1.0, round($regularPrice - $val));
        }

        if ($type === 'fixed_price') {
            return max(1.0, round($val));
        }

        return $regularPrice;
    }

    /**
     * Get active discount percentage for badge display (-18%).
     * Returns null if no active offer exists.
     */
    public function getActiveDiscountPercentAttribute(): ?int
    {
        // 1. Direct product-specific active offer
        if ($this->isProductOfferActive() && (float) ($this->offer_discount_value ?? 0) > 0) {
            if ($this->offer_discount_type === 'percentage') {
                return (int) round($this->offer_discount_value);
            }

            $orig = (float) ($this->offer_regular_price ?: $this->original_price);
            $curr = (float) $this->base_price;
            if ($orig > 0 && $orig > $curr) {
                return (int) round((($orig - $curr) / $orig) * 100);
            }

            return (int) round($this->offer_discount_value);
        }

        // 2. Active Category Offer
        $offer = $this->active_offer;
        if ($offer && $offer->is_active) {
            if ($offer->discount_type === 'percentage') {
                return (int) round($offer->discount_value);
            }

            $orig = (float) $this->original_price;
            $curr = (float) $this->base_price;
            if ($orig > 0 && $orig > $curr) {
                return (int) round((($orig - $curr) / $orig) * 100);
            }

            return (int) round($offer->discount_value);
        }

        // Only show discount badge if a specific product offer or category offer is active
        return null;
    }

    /**
     * Get the original (compare/old) price for the product.
     */
    public function getOriginalPriceAttribute(): float
    {
        $firstVariant = $this->relationLoaded('variants')
            ? $this->variants->first()
            : $this->variants()->first();

        $orig = (float) ($firstVariant?->original_price ?? 0);
        if ($orig > 0) {
            return $orig;
        }

        if ($this->isProductOfferActive() && (float) ($this->offer_regular_price ?? 0) > 0) {
            return (float) $this->offer_regular_price;
        }

        return 0.0;
    }

    /**
     * Get the total sold (with optional simulated metrics).
     */
    public function cloneTotalSold()
    {
        return $this->attributes['total_sold'] ?? 0;
    }

    public function getTotalSoldAttribute($value)
    {
        if ($this->metrics_mode === 'optimized' && $this->metrics_start_date) {
            $start = \Carbon\Carbon::parse($this->metrics_start_date)->startOfDay();
            $now = \Carbon\Carbon::now();
            
            if ($now->lessThan($start)) {
                return (int) $value;
            }
            
            $hoursPassed = (int) $start->diffInHours($now);
            $daysPassed = (int) floor($hoursPassed / 24);
            $currentHour = $hoursPassed % 24;
            
            $totalFake = 0;
            $dailyAvg = $this->metrics_daily_avg ?: 1;
            
            for ($i = 0; $i < $daysPassed; $i++) {
                mt_srand($this->id + $i);
                $min = max(0, (int)ceil($dailyAvg * 0.5));
                $max = (int)ceil($dailyAvg * 1.5);
                $totalFake += mt_rand($min, max($min, $max));
            }
            
            mt_srand($this->id + $daysPassed);
            $min = max(0, (int)ceil($dailyAvg * 0.5));
            $max = (int)ceil($dailyAvg * 1.5);
            $todayTotal = mt_rand($min, max($min, $max));
            
            $todayCurrent = floor(($todayTotal / 24) * $currentHour);
            $totalFake += $todayCurrent;
            
            mt_srand();
            
            return (int) $value + $totalFake;
        }
        
        return (int) $value;
    }

    /**
     * Get deterministic fake rating data for the product.
     */
    private function getFakeRatingData()
    {
        if ($this->rating_metrics_mode !== 'optimized' || !$this->rating_metrics_start_date) {
            return ['count' => 0, 'average' => 0];
        }

        $startDate = \Carbon\Carbon::parse($this->rating_metrics_start_date)->startOfDay();
        $daysPassed = $startDate->diffInDays(\Carbon\Carbon::now(), false);

        if ($daysPassed < 0) {
            return ['count' => 0, 'average' => 0];
        }

        $intervalDays = max(1, (int) $this->rating_metrics_interval_days);
        $countPerInterval = (int) $this->rating_metrics_count;
        $minRating = (float) $this->rating_metrics_min;
        $maxRating = (float) $this->rating_metrics_max;
        
        // Add a deterministic offset based on product ID so not all products trigger on same day
        $offset = $this->id % $intervalDays;
        
        $intervalsPassed = floor(($daysPassed + $offset) / $intervalDays);
        $fakeCount = $intervalsPassed * $countPerInterval;
        
        if ($fakeCount <= 0) {
            return ['count' => 0, 'average' => 0];
        }

        // Generate a deterministic fake average rating
        mt_srand($this->id);
        $fakeAvg = $minRating + mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax() * ($maxRating - $minRating);
        mt_srand(); // reset seed

        return [
            'count' => (int) $fakeCount,
            'average' => round($fakeAvg, 1)
        ];
    }

    public function getTotalReviewsAttribute($value)
    {
        $realCount = (int) $value;
        $fakeData = $this->getFakeRatingData();
        return $realCount + $fakeData['count'];
    }

    public function getAverageRatingAttribute($value)
    {
        $realAvg = (float) $value;
        $realCount = (int) ($this->attributes['total_reviews'] ?? 0);
        
        $fakeData = $this->getFakeRatingData();
        $fakeCount = $fakeData['count'];
        $fakeAvg = $fakeData['average'];
        
        $totalCount = $realCount + $fakeCount;
        
        if ($totalCount == 0) {
            return 0.00;
        }

        $blendedAvg = (($realAvg * $realCount) + ($fakeAvg * $fakeCount)) / $totalCount;
        return round($blendedAvg, 2);
    }

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand()
    {
        return $this->belongsTo(\App\Models\Product\Brand::class);
    }

    /**
     * Get the questions for the product.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(\App\Models\ProductQuestion::class);
    }

    /**
     * Get daily view statistics for the product.
     */
    public function viewStats(): HasMany
    {
        return $this->hasMany(\App\Models\ProductViewStat::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($product) {
            \Illuminate\Support\Facades\Cache::forget('homepage_trending');
            \Illuminate\Support\Facades\Cache::forget('homepage_sections');
        });

        static::deleted(function ($product) {
            \Illuminate\Support\Facades\Cache::forget('homepage_trending');
            \Illuminate\Support\Facades\Cache::forget('homepage_sections');
        });
    }
}
