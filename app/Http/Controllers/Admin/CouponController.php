<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Category;
use App\Models\Product\Product;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<', now());
            }
        }

        $coupons = $query->paginate(15)->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function getProducts(Request $request)
    {
        $query = Product::select('id', 'title', 'base_price', 'featured_image');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('title', 'like', "%{$q}%");
        }

        $paginator = $query->orderBy('title')->paginate(10);

        $items = collect($paginator->items())->map(function ($product) {
            $imageUrl = null;
            if ($product->featured_image) {
                $imageUrl = \Illuminate\Support\Str::startsWith($product->featured_image, 'http')
                    ? $product->featured_image
                    : \Illuminate\Support\Facades\Storage::url($product->featured_image);
            }
            return [
                'id' => $product->id,
                'title' => $product->title,
                'base_price' => (float) $product->base_price,
                'image_url' => $imageUrl,
            ];
        });

        return response()->json([
            'data' => $items,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $targetIds = old('target_ids', []);
        $selectedProducts = !empty($targetIds)
            ? Product::whereIn('id', (array)$targetIds)->get(['id', 'title', 'base_price', 'featured_image'])
            : collect();

        $selectedProducts = $selectedProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'base_price' => (float) $product->base_price,
                'image_url' => $product->featured_image
                    ? (\Illuminate\Support\Str::startsWith($product->featured_image, 'http') ? $product->featured_image : \Illuminate\Support\Facades\Storage::url($product->featured_image))
                    : null,
            ];
        });

        return view('admin.coupons.create', compact('categories', 'selectedProducts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:fixed,percentage,free_shipping',
            'discount_amount' => 'required_unless:discount_type,free_shipping|nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'target_type' => 'required|in:all,category,product',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'first_order_only' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['discount_amount'] = $validated['discount_type'] === 'free_shipping' ? 0 : ($validated['discount_amount'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['first_order_only'] = $request->boolean('first_order_only', false);

        if ($validated['target_type'] === 'all') {
            $validated['target_ids'] = null;
        }

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully!');
    }

    public function edit(Coupon $coupon)
    {
        $categories = Category::orderBy('name')->get();
        $targetIds = old('target_ids', $coupon->target_ids ?? []);
        if (!is_array($targetIds)) $targetIds = [];

        $selectedProducts = !empty($targetIds)
            ? Product::whereIn('id', $targetIds)->get(['id', 'title', 'base_price', 'featured_image'])
            : collect();

        $selectedProducts = $selectedProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'base_price' => (float) $product->base_price,
                'image_url' => $product->featured_image
                    ? (\Illuminate\Support\Str::startsWith($product->featured_image, 'http') ? $product->featured_image : \Illuminate\Support\Facades\Storage::url($product->featured_image))
                    : null,
            ];
        });

        return view('admin.coupons.edit', compact('coupon', 'categories', 'selectedProducts'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:fixed,percentage,free_shipping',
            'discount_amount' => 'required_unless:discount_type,free_shipping|nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'target_type' => 'required|in:all,category,product',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'first_order_only' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['discount_amount'] = $validated['discount_type'] === 'free_shipping' ? 0 : ($validated['discount_amount'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['first_order_only'] = $request->boolean('first_order_only', false);

        if ($validated['target_type'] === 'all') {
            $validated['target_ids'] = null;
        }

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully!');
    }

    public function toggleStatus(Coupon $coupon)
    {
        $coupon->is_active = !$coupon->is_active;
        $coupon->save();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $coupon->is_active,
                'message' => 'Status updated successfully!'
            ]);
        }

        return back()->with('success', 'Coupon status updated successfully!');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully!');
    }
}
