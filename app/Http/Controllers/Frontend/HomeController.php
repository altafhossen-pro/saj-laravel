<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    protected function getProductColumns(): array
    {
        return [
            'id', 'title', 'slug', 'featured_image', 'category_id', 'base_price',
            'average_rating', 'total_sold', 'total_reviews',
            'metrics_mode', 'metrics_start_date', 'metrics_daily_avg',
            'rating_metrics_mode', 'rating_metrics_start_date', 'rating_metrics_interval_days',
            'rating_metrics_count', 'rating_metrics_min', 'rating_metrics_max',
            'is_offer_active', 'offer_title', 'offer_discount_type', 'offer_discount_value',
            'offer_regular_price', 'offer_start_date', 'offer_end_date',
        ];
    }

    public function index()
    {
        $heroSlides = Cache::rememberForever('homepage_hero_slides', function () {
            return \App\Models\HeroSlide::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['type', 'content', 'mobile_content', 'link']);
        });

        $productColumns = $this->getProductColumns();

        $homepageCategories = Cache::remember('homepage_sections', 60 * 6, function () use ($productColumns) {
            $categories = Category::where('show_on_homepage', true)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
                
            foreach($categories as $category) {
                $categoryIds = $category->getAllDescendantIds();
                $productsQuery = Product::whereIn('category_id', $categoryIds)
                    ->where('is_active', true);

                switch ($category->homepage_product_sort) {
                    case 'oldest':
                        $productsQuery->oldest();
                        break;
                    case 'random':
                        $productsQuery->inRandomOrder();
                        break;
                    case 'popular':
                        $productsQuery->orderBy('total_sold', 'desc');
                        break;
                    case 'price_low_high':
                        $productsQuery->orderBy('base_price', 'asc');
                        break;
                    case 'price_high_low':
                        $productsQuery->orderBy('base_price', 'desc');
                        break;
                    case 'latest':
                    default:
                        $productsQuery->latest();
                        break;
                }

                $category->homepage_products = $productsQuery
                    ->take($category->homepage_max_products)
                    ->get($productColumns);
            }
            return $categories;
        });

        // Fetch initial 20 products for Just For You
        $justForYouProducts = (function() use ($productColumns) {
            $type = Setting::get('just_for_you_type', 'latest');
            $priorityCategoryIds = json_decode(Setting::get('just_for_you_priority_categories', '[]'), true) ?? [];
            $query = Product::where('is_active', true);
            
            if (!empty($priorityCategoryIds)) {
                $idsString = implode(',', $priorityCategoryIds);
                $query->orderByRaw("CASE WHEN category_id IN ($idsString) THEN 0 ELSE 1 END");
            }
            
            if ($type === 'random') {
                $seed = crc32(session()->getId() ?: 'default');
                $query->orderByRaw("RAND($seed)");
            } else {
                $query->latest();
            }
            
            return $query->paginate(20, $productColumns);
        })();

        $trendingSettings = [
            'layout' => Setting::get('trending_layout', 'grid'),
            'slider_max' => (int) Setting::get('trending_slider_max', 12),
            'slider_autoplay' => Setting::get('trending_slider_autoplay', 'true'),
            'slider_autoplay_duration' => (int) Setting::get('trending_slider_autoplay_duration', 3000),
            'sort_order' => Setting::get('trending_sort_order', 'latest'),
            'grid_initial' => (int) Setting::get('trending_grid_initial', 6),
            'grid_load_more' => (int) Setting::get('trending_grid_load_more', 6),
            'grid_max' => (int) Setting::get('trending_grid_max', 24),
        ];

        $trendingProducts = Cache::remember('homepage_trending', 60 * 6, function () use ($trendingSettings, $productColumns) {
            $query = Product::where('is_active', true)
                ->where('is_trending', true);
                
            if ($trendingSettings['sort_order'] === 'random') {
                $query->inRandomOrder();
            } else {
                $query->latest();
            }
                
            if ($trendingSettings['layout'] === 'slider') {
                $query->take($trendingSettings['slider_max']);
            } else {
                $query->take($trendingSettings['grid_max']);
            }
            
            return $query->get($productColumns);
        });

        return view('welcome', compact('heroSlides', 'homepageCategories', 'justForYouProducts', 'trendingProducts', 'trendingSettings'));
    }

    public function justForYou()
    {
        $type = Setting::get('just_for_you_type', 'latest');
        $priorityCategoryIds = json_decode(Setting::get('just_for_you_priority_categories', '[]'), true) ?? [];
        $query = Product::where('is_active', true);
        
        if (!empty($priorityCategoryIds)) {
            $idsString = implode(',', $priorityCategoryIds);
            $query->orderByRaw("CASE WHEN category_id IN ($idsString) THEN 0 ELSE 1 END");
        }
        
        if ($type === 'random') {
            $seed = crc32(session()->getId() ?: 'default');
            $query->orderByRaw("RAND($seed)");
        } else {
            $query->latest();
        }
        
        return $query->paginate(20, $this->getProductColumns());
    }

    public function checkout()
    {
        $deliveryCharges = Cache::rememberForever('delivery_charges', function () {
            return [
                'inside' => (int) Setting::get('delivery_charge_inside_dhaka', 70),
                'sub' => (int) Setting::get('delivery_charge_sub_dhaka', 110),
                'outside' => (int) Setting::get('delivery_charge_outside_dhaka', 130),
                'free_min_order' => (int) Setting::get('delivery_free_min_order', 3000),
            ];
        });

        return view('frontend.checkout', compact('deliveryCharges'));
    }
}
