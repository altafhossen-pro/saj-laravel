<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show($slug)
    {
        $product = Product::with(['variants', 'category', 'brand'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            $suggestedProducts = Product::where('is_active', true)
                ->inRandomOrder()
                ->take(10)
                ->get();
                
            return response()->view('frontend.product.not-found', compact('suggestedProducts', 'slug'), 404);
        }

        // --- High-Performance View Tracking (Deduplicated per visitor session) ---
        try {
            $viewedKey = 'viewed_p_' . $product->id;
            if (!session()->has($viewedKey)) {
                session()->put($viewedKey, time());

                // 1. Increment total lifetime views directly on DB level (zero Eloquent dirty-state overhead)
                Product::where('id', $product->id)->increment('views_count');

                // 2. High-performance aggregate daily stats upsert
                $todayDate = now()->toDateString();
                \App\Models\ProductViewStat::upsert(
                    [
                        'product_id' => $product->id,
                        'view_date' => $todayDate,
                        'views_count' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    ['product_id', 'view_date'],
                    ['views_count' => \Illuminate\Support\Facades\DB::raw('product_view_stats.views_count + 1'), 'updated_at' => now()]
                );
            }
        } catch (\Throwable $e) {
            // Failsafe: Log or ignore so page rendering never breaks
        }

        $words = array_filter(explode(' ', preg_replace('/[^a-zA-Z0-9]+/', ' ', strtolower($product->title))), function($w) {
            return strlen($w) > 3; // Focus on words > 3 chars
        });

        // 1. Fetch a pool of products for this category (cached for 1 hour to be super fast)
        $categoryPool = Cache::remember('category_pool_' . $product->category_id, 60 * 60, function () use ($product) {
            return Product::where('is_active', true)
                ->where('category_id', $product->category_id)
                ->take(200) // limit to 200 to save memory, enough for randomization
                ->get(['id', 'title', 'slug', 'featured_image', 'base_price', 'average_rating', 'total_sold', 'total_reviews', 'category_id', 'rating_metrics_mode', 'rating_metrics_start_date', 'rating_metrics_interval_days', 'rating_metrics_count', 'rating_metrics_min', 'rating_metrics_max', 'metrics_mode', 'metrics_start_date', 'metrics_daily_avg']);
        });

        // Filter out the current product
        $filteredPool = $categoryPool->where('id', '!=', $product->id);

        // 2. Score them dynamically on EVERY reload (fast PHP array sorting)
        $similarProducts = $filteredPool->sortByDesc(function ($p) use ($words) {
            $score = 0;
            $title = strtolower($p->title);
            
            // Priority to name matches
            foreach ($words as $word) {
                if (strpos($title, $word) !== false) {
                    $score += 10; // High priority score
                }
            }
            
            // Add a random fraction to break ties randomly! 
            $score += (mt_rand(0, 99) / 100); 

            return $score;
        })->values()->take(10);

        // 3. Fill the gap if less than 10
        if ($similarProducts->count() < 10) {
            // We can just fetch randomly from DB for the remaining, without cache since it's a fallback
            $extraProducts = Product::where('is_active', true)
                ->where('id', '!=', $product->id)
                ->where('category_id', '!=', $product->category_id)
                ->inRandomOrder()
                ->take(10 - $similarProducts->count())
                ->get(['id', 'title', 'slug', 'featured_image', 'base_price', 'average_rating', 'total_sold', 'total_reviews', 'category_id', 'rating_metrics_mode', 'rating_metrics_start_date', 'rating_metrics_interval_days', 'rating_metrics_count', 'rating_metrics_min', 'rating_metrics_max', 'metrics_mode', 'metrics_start_date', 'metrics_daily_avg']);
                
            $similarProducts = $similarProducts->concat($extraProducts);
        }

        $myQuestionsCount = 0;
        $initialMyQuestions = collect();
        
        $answeredQuery = $product->questions()->where('status', 'answered');
        
        if (auth()->check()) {
            $myQuestionsQuery = $product->questions()
                ->where('user_id', auth()->id())
                ->latest();
                
            $myQuestionsCount = (clone $myQuestionsQuery)->count();
            $initialMyQuestions = (clone $myQuestionsQuery)->take(5)->get()->map(function($q) {
                $q->time_ago = $q->created_at->diffForHumans();
                $q->answer_time_ago = $q->updated_at->diffForHumans();
                return $q;
            });
            
            $answeredQuery->where('user_id', '!=', auth()->id());
        }

        $answeredQuestionsCount = (clone $answeredQuery)->count();
        $initialOtherQuestions = (clone $answeredQuery)->latest()->take(5)->get()->map(function($q) {
            $q->time_ago = $q->created_at->diffForHumans();
            $q->answer_time_ago = $q->updated_at->diffForHumans();
            return $q;
        });

        $productReviews = \App\Models\Product\ProductReview::where('product_id', $product->id)
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->paginate(5); // Just in case they want pagination later, but we can pass paginator

        return view('product-details', [
            'product' => $product, 
            'similarProducts' => $similarProducts,
            'myQuestionsCount' => $myQuestionsCount,
            'initialMyQuestions' => $initialMyQuestions,
            'answeredQuestionsCount' => $answeredQuestionsCount,
            'initialOtherQuestions' => $initialOtherQuestions,
            'productReviews' => $productReviews
        ]);
    }

    public function getQuestions(Request $request, Product $product)
    {
        $type = $request->input('type', 'answered');

        if ($type === 'my' && auth()->check()) {
            $questions = $product->questions()
                ->where('user_id', auth()->id())
                ->latest()
                ->paginate(5);
        } else {
            $query = $product->questions()->where('status', 'answered');
            if (auth()->check()) {
                $query->where('user_id', '!=', auth()->id());
            }
            $questions = $query->latest()->paginate(5);
        }
        
        // Add time_ago attributes
        $questions->getCollection()->transform(function ($q) {
            $q->time_ago = $q->created_at->diffForHumans();
            $q->answer_time_ago = $q->updated_at->diffForHumans();
            return $q;
        });
            
        return response()->json($questions);
    }

    public function storeQuestion(Request $request, Product $product)
    {
        $rules = [
            'question' => 'required|string|min:5|max:1000'
        ];

        if (!auth()->check()) {
            $rules['customer_name'] = 'required|string|max:255';
            $rules['phone'] = 'required|string|max:20';
        }

        $request->validate($rules);

        $question = $product->questions()->create([
            'question' => $request->question,
            'user_id' => auth()->id(),
            'customer_name' => auth()->check() ? auth()->user()->name : $request->customer_name,
            'phone' => auth()->check() ? auth()->user()->phone ?? null : $request->phone,
            'status' => 'pending'
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your question has been submitted successfully and will be published once answered.',
                'question' => $question
            ]);
        }

        return back()->with('success', 'Your question has been submitted successfully and will be published once answered.');
    }
}
