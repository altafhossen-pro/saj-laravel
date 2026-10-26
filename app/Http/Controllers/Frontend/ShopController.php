<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Cache;

class ShopController extends Controller
{
    public function category($category_slug)
    {
        $category = Cache::rememberForever('category_slug_' . $category_slug, function () use ($category_slug) {
            return Category::where('slug', $category_slug)->firstOrFail();
        });
        
        // Get this category and all its subcategories recursively (this is also cached internally)
        $categoryIds = $category->getAllDescendantIds();
        
        $query = Product::whereIn('category_id', $categoryIds)
            ->where('is_active', true);

        // Apply Filters
        if (request()->filled('min_price')) {
            $query->where('base_price', '>=', request('min_price'));
        }
        
        if (request()->filled('max_price')) {
            $query->where('base_price', '<=', request('max_price'));
        }
        
        if (request()->filled('rating')) {
            $query->where('average_rating', '>=', request('rating'));
        }

        // Apply Sorting
        $sort = request('sort', 'latest');
        switch ($sort) {
            case 'price_low_high':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_high_low':
                $query->orderBy('base_price', 'desc');
                break;
            case 'popular':
                $query->orderBy('total_sold', 'desc');
                break;
            case 'rating':
                $query->orderBy('average_rating', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate(20)->withQueryString();

        // Meta CAPI ViewCategory Track
        try {
            \App\Services\FacebookCapiService::trackViewCategory($category->name, $products->pluck('id')->take(10)->toArray());
        } catch (\Throwable $fbEx) {}
            
        return view('frontend.category', [
            'activeCategorySlug' => $category_slug,
            'category' => $category,
            'products' => $products,
        ]);
    }

    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));
        
        if (strlen($query) < 1) {
            return response()->json(['results' => [], 'has_more' => false]);
        }

        $products = Product::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('tags', 'like', '%' . $query . '%')
                  ->orWhereHas('variants', function($vq) use ($query) {
                      $vq->where('sku', 'like', '%' . $query . '%')
                         ->orWhere('barcode', 'like', '%' . $query . '%');
                  })
                  ->orWhereHas('category', function($cq) use ($query) {
                      $cq->where('name', 'like', '%' . $query . '%');
                  })
                  ->orWhereHas('brand', function($bq) use ($query) {
                      $bq->where('name', 'like', '%' . $query . '%');
                  });
            })
            ->take(6)
            ->get(['id', 'title', 'slug', 'featured_image', 'base_price', 'price_range']);

        $totalMatches = $products->count();
        $hasMore = $totalMatches > 5;
        if ($hasMore) {
            $products->pop(); // Remove the 6th item
        }

        // Intelligently log user search query (even when user only types and doesn't submit or gets no results)
        // Deduplicate within 20 seconds so rapid typing doesn't create duplicate logs for the same user
        if (strlen($query) >= 2) {
            try {
                $dedupKey = 's_logged_' . md5(strtolower($query) . '_' . ($request->ip() ?? ''));
                if (!\Illuminate\Support\Facades\Cache::has($dedupKey)) {
                    \Illuminate\Support\Facades\Cache::put($dedupKey, 1, 20); // 20s deduplication

                    \App\Models\SearchLog::create([
                        'query' => mb_substr($query, 0, 255),
                        'source' => $totalMatches === 0 ? 'no_result' : 'live_search',
                        'hits_count' => $totalMatches,
                        'clicked_product_id' => null,
                        'user_id' => auth()->id(),
                        'ip_address' => $request->ip(),
                    ]);

                    // Meta CAPI Search Track (Server-side deduplicated live search)
                    try {
                        \App\Services\FacebookCapiService::trackSearch($query);
                    } catch (\Throwable $fbEx) {}
                }
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'results' => $products,
            'has_more' => $hasMore
        ]);
    }

    public function searchPage(Request $request)
    {
        $searchQuery = trim($request->input('q', ''));
        
        // Log direct search action (Button press / Enter key)
        if (strlen($searchQuery) > 0) {
            try {
                \App\Models\SearchLog::create([
                    'query' => mb_substr($searchQuery, 0, 255),
                    'source' => 'direct',
                    'clicked_product_id' => null,
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $e) {}

            // Meta CAPI Search Track
            try {
                \App\Services\FacebookCapiService::trackSearch($searchQuery);
            } catch (\Throwable $fbEx) {}
        }

        $query = Product::where('is_active', true)
            ->where(function($q) use ($searchQuery) {
                $q->where('title', 'like', '%' . $searchQuery . '%')
                  ->orWhere('tags', 'like', '%' . $searchQuery . '%')
                  ->orWhereHas('variants', function($vq) use ($searchQuery) {
                      $vq->where('sku', 'like', '%' . $searchQuery . '%')
                         ->orWhere('barcode', 'like', '%' . $searchQuery . '%');
                  })
                  ->orWhereHas('category', function($cq) use ($searchQuery) {
                      $cq->where('name', 'like', '%' . $searchQuery . '%');
                  })
                  ->orWhereHas('brand', function($bq) use ($searchQuery) {
                      $bq->where('name', 'like', '%' . $searchQuery . '%');
                  });
            });

        // Apply Filters
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }
        
        if ($request->filled('rating')) {
            $query->where('average_rating', '>=', $request->rating);
        }

        // Apply Sorting
        $sort = $request->input('sort', 'latest');
        switch ($sort) {
            case 'price_low_high':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_high_low':
                $query->orderBy('base_price', 'desc');
                break;
            case 'popular':
                $query->orderBy('total_sold', 'desc');
                break;
            case 'rating':
                $query->orderBy('average_rating', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate(20)->withQueryString();
            
        return view('frontend.search', [
            'searchQuery' => $searchQuery,
            'products' => $products,
        ]);
    }

    /**
     * Ultra-fast background logger for suggestion clicks
     */
    public function logSearchClick(Request $request)
    {
        $query = trim($request->input('q', ''));
        $productId = $request->input('product_id');
        $source = $request->input('source', 'suggestion');

        if (strlen($query) > 0 || $productId) {
            try {
                \App\Models\SearchLog::create([
                    'query' => mb_substr($query ?: 'Clicked Product', 0, 255),
                    'source' => $source === 'suggestion' ? 'suggestion' : 'direct',
                    'clicked_product_id' => $productId ? (int)$productId : null,
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $e) {}
        }

        return response()->json(['status' => 'ok']);
    }
}
