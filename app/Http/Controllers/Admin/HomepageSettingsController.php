<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class HomepageSettingsController extends Controller
{
    public function justForYou()
    {
        $type = Setting::get('just_for_you_type', 'latest'); // latest or random
        $priorityCategories = json_decode(Setting::get('just_for_you_priority_categories', '[]'), true) ?? [];
        
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return view('admin.homepage.just-for-you', compact('type', 'priorityCategories', 'categories'));
    }

    public function saveJustForYou(Request $request)
    {
        $request->validate([
            'type' => 'required|in:latest,random',
            'priority_categories' => 'nullable|array',
            'priority_categories.*' => 'exists:categories,id'
        ]);

        Setting::set('just_for_you_type', $request->type);
        Setting::set('just_for_you_priority_categories', json_encode($request->priority_categories ?? []));
        
        // Clear homepage cache just in case we use it later
        Cache::forget('homepage_just_for_you');

        return redirect()->back()->with('success', 'Just For You settings updated successfully!');
    }
    
    public function trending()
    {
        $settings = [
            'layout' => Setting::get('trending_layout', 'grid'),
            'slider_max' => Setting::get('trending_slider_max', 12),
            'slider_autoplay' => Setting::get('trending_slider_autoplay', 'true'),
            'slider_autoplay_duration' => Setting::get('trending_slider_autoplay_duration', 3000),
            'sort_order' => Setting::get('trending_sort_order', 'latest'),
            'grid_initial' => Setting::get('trending_grid_initial', 6),
            'grid_load_more' => Setting::get('trending_grid_load_more', 6),
            'grid_max' => Setting::get('trending_grid_max', 24),
        ];

        $trendingProducts = \App\Models\Product\Product::where('is_trending', true)
            ->orderBy('title')
            ->get();
            
        return view('admin.homepage.trending', compact('trendingProducts', 'settings'));
    }

    public function saveTrendingSettings(Request $request)
    {
        $request->validate([
            'layout' => 'required|in:slider,grid',
            'slider_max' => 'required|integer|min:1',
            'slider_autoplay' => 'required|in:true,false',
            'slider_autoplay_duration' => 'required_if:slider_autoplay,true|integer|min:500',
            'sort_order' => 'required|in:latest,random',
            'grid_initial' => 'required|integer|min:1',
            'grid_load_more' => 'required|integer|min:1',
            'grid_max' => 'required|integer|min:1',
        ]);

        Setting::set('trending_layout', $request->layout);
        Setting::set('trending_slider_max', $request->slider_max);
        Setting::set('trending_slider_autoplay', $request->slider_autoplay);
        Setting::set('trending_slider_autoplay_duration', $request->slider_autoplay_duration);
        Setting::set('trending_sort_order', $request->sort_order);
        Setting::set('trending_grid_initial', $request->grid_initial);
        Setting::set('trending_grid_load_more', $request->grid_load_more);
        Setting::set('trending_grid_max', $request->grid_max);

        // Clear cache
        Cache::forget('homepage_trending');

        return redirect()->back()->with('success', 'Trending layout settings updated successfully!');
    }

    public function addTrending(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = \App\Models\Product\Product::findOrFail($request->product_id);
        $product->is_trending = true;
        $product->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product added to Trending Now successfully!',
                'product' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'is_trending' => true
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Product added to Trending Now successfully!');
    }

    public function removeTrending(\App\Models\Product\Product $product)
    {
        $product->is_trending = false;
        $product->save();

        return redirect()->back()->with('success', 'Product removed from Trending Now.');
    }

    public function searchProducts(Request $request)
    {
        $query = $request->get('q');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $products = \App\Models\Product\Product::where('title', 'like', '%' . $query . '%')
            ->select('id', 'title', 'featured_image', 'is_trending', 'base_price')
            ->withCount('variants')
            ->take(10)
            ->get();

        $products->map(function ($product) {
            $product->image_url = $product->featured_image ? (\Illuminate\Support\Str::startsWith($product->featured_image, 'http') ? $product->featured_image : \Illuminate\Support\Facades\Storage::url($product->featured_image)) : 'https://placehold.co/100x100?text=No+Image';
            return $product;
        });

        return response()->json($products);
    }

    public function categorySections()
    {
        $categories = Category::with('parent')
            ->withCount(['children', 'products'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.homepage.category-sections', compact('categories'));
    }

    public function reorderCategorySections(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:categories,id',
        ]);

        foreach ($request->order as $index => $id) {
            Category::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        Cache::forget('homepage_sections');

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully.'
        ]);
    }
}
