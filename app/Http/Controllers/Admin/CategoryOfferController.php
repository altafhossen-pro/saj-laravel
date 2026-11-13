<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryOfferController extends Controller
{
    public function index(Request $request)
    {
        // Load main categories with children and their respective offer relations
        $categories = Category::whereNull('parent_id')
            ->with(['children.children.offer', 'children.offer', 'offer'])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Calculate summary statistics
        $activeOffersCount = CategoryOffer::where('is_active', true)->count();
        $totalDiscountedProducts = CategoryOffer::where('is_active', true)->sum('products_affected_count');
        $totalCategoriesCount = Category::count();

        $stats = [
            'total_categories' => $totalCategoriesCount,
            'active_offers' => $activeOffersCount,
            'total_discounted_products' => $totalDiscountedProducts,
        ];

        return view('admin.category-offers.index', compact('categories', 'stats'));
    }

    /**
     * Save or update discount directly from a row in the table.
     */
    public function saveRow(Request $request, Category $category)
    {
        $validated = $request->validate([
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|integer|min:0' . ($request->discount_type === 'percentage' ? '|max:100' : ''),
            'include_subcategories' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ], [
            'discount_value.integer' => 'ডিসকাউন্টের পরিমাণ অবশ্যই একটি পূর্ণ সংখ্যা (দশমিক ছাড়া) হতে হবে।',
        ]);

        $value = (int) $validated['discount_value'];
        $isActive = $request->boolean('is_active', true);
        $includeSubs = $request->boolean('include_subcategories', true);

        // Find or create offer record for this category
        $offer = CategoryOffer::firstOrNew(['category_id' => $category->id]);
        $offer->title = "{$category->name} Discount";
        $offer->discount_type = $validated['discount_type'];
        $offer->discount_value = $value;
        $offer->include_subcategories = $includeSubs;

        if ($isActive && $value > 0) {
            // Apply or Re-apply discount
            $affected = $offer->applyDiscounts();
            $msg = "{$category->name}-এ " . ($offer->discount_type === 'percentage' ? "{$value}%" : "৳{$value}") . " ছাড় সক্রিয় করা হয়েছে এবং {$affected}টি প্রোডাক্টের প্রাইস ও ওল্ড প্রাইস অটো আপডেট হয়েছে।";
        } else {
            // Deactivate and restore prices
            $reverted = $offer->revertDiscounts();
            $msg = "{$category->name}-এর ছাড় নিষ্ক্রিয় করা হয়েছে এবং {$reverted}টি প্রোডাক্টের আসল দাম ফিরিয়ে আনা হয়েছে।";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Instant toggle active/inactive from the switch in table row (supports AJAX & Form).
     */
    public function toggle(Request $request, Category $category)
    {
        $offer = CategoryOffer::firstOrNew(['category_id' => $category->id]);

        // If client sent updated values during toggle
        if ($request->filled('discount_value')) {
            $offer->discount_value = (int) $request->input('discount_value', 0);
        }
        if ($request->filled('discount_type')) {
            $offer->discount_type = $request->input('discount_type');
        }
        if ($request->has('include_subcategories')) {
            $offer->include_subcategories = $request->boolean('include_subcategories', true);
        }
        if (!$offer->title) {
            $offer->title = "{$category->name} Discount";
        }

        if ((int)$offer->discount_value <= 0) {
            $errorMsg = 'দয়া করে আগে ডিসকাউন্টের পরিমাণ (যেমন: 10%) বসিয়ে Save বাটনে ক্লিক করুন।';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 422);
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        if ($offer->is_active) {
            $count = $offer->revertDiscounts();
            $actionMsg = "{$category->name}-এর অফার নিষ্ক্রিয় করা হয়েছে এবং {$count}টি প্রোডাক্টের আসল দাম ফিরিয়ে আনা হয়েছে।";
            $isActive = false;
        } else {
            $count = $offer->applyDiscounts();
            $actionMsg = "{$category->name}-এর অফার সক্রিয় করা হয়েছে এবং {$count}টি প্রোডাক্টের প্রাইস অটো আপডেট হয়েছে।";
            $isActive = true;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $isActive,
                'message' => 'Saved',
                'detail' => $actionMsg,
                'affected_count' => $count,
            ]);
        }

        return redirect()->back()->with('success', "Saved! {$actionMsg}");
    }

    /**
     * Bulk apply or revert discounts across multiple selected categories.
     */
    public function bulkApply(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'bulk_action' => 'required|in:apply,revert',
            'bulk_discount_type' => 'nullable|in:percentage,fixed',
            'bulk_discount_value' => 'nullable|integer|min:0' . ($request->bulk_discount_type === 'percentage' ? '|max:100' : ''),
            'include_subcategories' => 'nullable|boolean',
        ], [
            'bulk_discount_value.integer' => 'ডিসকাউন্টের পরিমাণ অবশ্যই একটি পূর্ণ সংখ্যা (দশমিক ছাড়া) হতে হবে।',
        ]);

        $categoryIds = $request->category_ids;
        $action = $request->bulk_action;
        $type = $request->input('bulk_discount_type', 'percentage');
        $value = (int) $request->input('bulk_discount_value', 0);
        $includeSubs = $request->boolean('include_subcategories', true);

        if ($action === 'apply' && $value <= 0) {
            return redirect()->back()->with('error', 'বাল্ক ডিসকাউন্ট দেওয়ার জন্য অবশ্যই একটি পরিমাণ (যেমন: 10%) উল্লেখ করুন।');
        }

        $totalAffected = 0;

        DB::transaction(function () use ($categoryIds, $action, $type, $value, $includeSubs, &$totalAffected) {
            foreach ($categoryIds as $catId) {
                $category = Category::find($catId);
                if (!$category) continue;

                $offer = CategoryOffer::firstOrNew(['category_id' => $catId]);

                if ($action === 'apply') {
                    $offer->title = "{$category->name} Discount";
                    $offer->discount_type = $type;
                    $offer->discount_value = $value;
                    $offer->include_subcategories = $includeSubs;
                    $totalAffected += $offer->applyDiscounts();
                } else {
                    if ($offer->exists && $offer->is_active) {
                        $totalAffected += $offer->revertDiscounts();
                    }
                }
            }
        });

        if ($action === 'apply') {
            return redirect()->back()->with('success', "সিলেক্ট করা " . count($categoryIds) . "টি ক্যাটাগরির ওপর " . ($type === 'percentage' ? "{$value}%" : "৳{$value}") . " ছাড় কার্যকর হয়েছে এবং মোট {$totalAffected}টি প্রোডাক্টের প্রাইস ও ওল্ড প্রাইস অটো আপডেট হয়েছে।");
        } else {
            return redirect()->back()->with('success', "সিলেক্ট করা ক্যাটাগরিগুলোর ছাড় বন্ধ করা হয়েছে এবং প্রোডাক্টের আসল দাম ফিরিয়ে আনা হয়েছে।");
        }
    }
}
