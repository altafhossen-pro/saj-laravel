<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductReview::with(['product', 'user']);

        // Search by user name, email, customer_name, or product title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($mainQ) use ($search) {
                $mainQ->where('customer_name', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('product', function($q) use ($search) {
                          $q->where('title', 'like', "%{$search}%");
                      });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $reviews = $query->latest()->paginate($perPage)->withQueryString();

        $allProducts = \App\Models\Product\Product::where('is_active', true)
            ->select('id', 'title', 'featured_image')
            ->orderBy('title')
            ->get();

        return view('admin.reviews.index', compact('reviews', 'allProducts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_name' => 'required|string|max:100',
            'customer_avatar' => 'nullable|image|max:2048',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
            'status' => 'required|in:pending,approved,rejected',
            'created_at' => 'nullable|date',
            'attachments.*' => 'nullable|image|max:3072',
        ]);

        $avatarPath = null;
        if ($request->hasFile('customer_avatar')) {
            $avatarPath = $request->file('customer_avatar')->store('reviews/avatars', 'public');
        }

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = $file->store('reviews', 'public');
            }
        }

        $review = new ProductReview();
        $review->product_id = $request->product_id;
        $review->user_id = null;
        $review->customer_name = trim($request->customer_name);
        $review->customer_avatar = $avatarPath;
        $review->rating = (int) $request->rating;
        $review->review = $request->review;
        $review->status = $request->status;
        $review->is_custom = true;
        $review->attachments = count($attachments) > 0 ? $attachments : null;

        if ($request->filled('created_at')) {
            $review->created_at = \Carbon\Carbon::parse($request->created_at);
        }

        $review->save();

        if ($review->status === 'approved') {
            $this->updateProductRatings($review->product_id);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Custom review created successfully.',
                'review' => $review
            ]);
        }

        return redirect()->back()->with('success', 'Custom review created successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $review = ProductReview::findOrFail($id);
        $review->status = $request->status;
        $review->save();

        // Update product real ratings
        $this->updateProductRatings($review->product_id);

        return redirect()->back()->with('success', 'Review status updated successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'customer_name' => 'nullable|string|max:100',
            'customer_avatar' => 'nullable|image|max:2048',
            'status' => 'required|in:pending,approved,rejected',
            'attachments.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072'
        ]);

        $reviewModel = ProductReview::findOrFail($id);
        $reviewModel->rating = $request->rating;
        $reviewModel->review = $request->review;
        $reviewModel->status = $request->status;

        if ($request->filled('customer_name')) {
            $reviewModel->customer_name = trim($request->customer_name);
        }

        if ($request->hasFile('customer_avatar')) {
            if ($reviewModel->customer_avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($reviewModel->customer_avatar)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($reviewModel->customer_avatar);
            }
            $reviewModel->customer_avatar = $request->file('customer_avatar')->store('reviews/avatars', 'public');
        }

        $attachments = is_array($reviewModel->attachments) ? $reviewModel->attachments : [];

        // Handle removals
        if ($request->has('remove_attachments')) {
            $removeKeys = json_decode($request->remove_attachments, true);
            if (is_array($removeKeys)) {
                foreach ($removeKeys as $key) {
                    if (isset($attachments[$key])) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachments[$key]);
                        unset($attachments[$key]);
                    }
                }
                $attachments = array_values($attachments); // Re-index
            }
        }

        // Handle new uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('reviews', 'public');
                $attachments[] = $path;
            }
        }

        $reviewModel->attachments = count($attachments) > 0 ? $attachments : null;
        $reviewModel->save();

        $this->updateProductRatings($reviewModel->product_id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully.',
                'review' => $reviewModel
            ]);
        }

        return redirect()->back()->with('success', 'Review updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $review = ProductReview::findOrFail($id);
        $product_id = $review->product_id;
        $review->delete();

        // Update product real ratings
        $this->updateProductRatings($product_id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Review deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }

    private function updateProductRatings($product_id)
    {
        $product = \App\Models\Product\Product::findOrFail($product_id);
        
        $approvedReviews = ProductReview::where('product_id', $product_id)
            ->where('status', 'approved')
            ->get();
            
        $product->total_reviews = $approvedReviews->count();
        $product->average_rating = $approvedReviews->count() > 0 ? $approvedReviews->avg('rating') : 0;
        
        // Prevent accessor loops or issues by raw saving or saving specific columns
        \Illuminate\Support\Facades\DB::table('products')
            ->where('id', $product_id)
            ->update([
                'total_reviews' => $product->total_reviews,
                'average_rating' => $product->average_rating
            ]);
    }
}
