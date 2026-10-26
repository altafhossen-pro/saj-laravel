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
    ];

    protected $appends = ['total_sold', 'original_price', 'total_reviews'];

    /**
     * Get the original (compare/old) price for the product.
     */
    public function getOriginalPriceAttribute(): float
    {
        $firstVariant = $this->relationLoaded('variants')
            ? $this->variants->first()
            : $this->variants()->first();

        return (float) ($firstVariant?->original_price ?? 0);
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
