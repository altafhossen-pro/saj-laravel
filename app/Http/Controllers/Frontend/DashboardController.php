<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $totalOrders = \App\Models\Order::where('user_id', $user->id)->count();
        $pendingOrders = \App\Models\Order::where('user_id', $user->id)->where('status', 'pending')->count();
        $deliveredOrders = \App\Models\Order::where('user_id', $user->id)->where('status', 'delivered')->count();
        $recentOrders = \App\Models\Order::where('user_id', $user->id)->latest()->take(5)->get();

        return view('dashboard', [
            'activeTab' => 'overview',
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'deliveredOrders' => $deliveredOrders,
            'recentOrders' => $recentOrders
        ]);
    }

    public function orders()
    {
        $orders = \App\Models\Order::with('items')->where('user_id', \Illuminate\Support\Facades\Auth::id())->latest()->paginate(10);
        return view('dashboard', ['activeTab' => 'orders', 'orders' => $orders]);
    }

    public function reviews()
    {
        $user_id = \Illuminate\Support\Facades\Auth::id();

        // Get delivered orders
        $deliveredOrderIds = \App\Models\Order::where('user_id', $user_id)
            ->where('status', 'delivered')
            ->pluck('id');

        // Get unique products from those orders, keeping the latest order item
        $orderItems = \App\Models\OrderItem::whereIn('order_id', $deliveredOrderIds)
            ->with(['product', 'order'])
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                return $items->sortByDesc(function ($item) {
                    return $item->order->updated_at ?? $item->created_at;
                })->first();
            })
            ->filter(function ($item) {
                return $item->product !== null;
            });

        // Get existing user reviews
        $existingReviews = \App\Models\Product\ProductReview::where('user_id', $user_id)
            ->get()
            ->keyBy('product_id');

        return view('dashboard', [
            'activeTab' => 'reviews',
            'orderItems' => $orderItems,
            'existingReviews' => $existingReviews
        ]);
    }

    public function writeReview($product_slug)
    {
        $user_id = \Illuminate\Support\Facades\Auth::id();

        $product = \App\Models\Product\Product::where('slug', $product_slug)->first();

        if (!$product) {
            return redirect()->route('dashboard.reviews')->with('error', 'Product not found or invalid link.');
        }

        // Verify user actually bought and received the product
        $hasBought = \App\Models\OrderItem::where('product_id', $product->id)
            ->whereHas('order', function($q) use ($user_id) {
                $q->where('user_id', $user_id)->where('status', 'delivered');
            })->exists();

        if (!$hasBought) {
            return redirect()->route('dashboard.reviews')->with('error', 'You can only review products you have purchased and received.');
        }

        $review = \App\Models\Product\ProductReview::where('user_id', $user_id)->where('product_id', $product->id)->first();

        // Check latest order date
        $latestOrder = \App\Models\OrderItem::where('product_id', $product->id)
            ->whereHas('order', function($q) use ($user_id) {
                $q->where('user_id', $user_id)->where('status', 'delivered');
            })
            ->with('order')
            ->get()
            ->sortByDesc(function ($item) {
                return $item->order->updated_at ?? $item->created_at;
            })->first();

        $latestOrderDate = $latestOrder ? ($latestOrder->order->updated_at ?? $latestOrder->created_at) : null;

        return view('frontend.write-review', compact('product', 'review', 'latestOrderDate'));
    }

    public function storeReview(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'attachments.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $user_id = \Illuminate\Support\Facades\Auth::id();

        // Verify user actually bought and received the product
        $hasBought = \App\Models\OrderItem::where('product_id', $request->product_id)
            ->whereHas('order', function($q) use ($user_id) {
                $q->where('user_id', $user_id)->where('status', 'delivered');
            })->exists();

        if (!$hasBought) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'You can only review products you have purchased and received.'], 403);
            }
            return back()->with('error', 'You can only review products you have purchased and received.');
        }

        // Check if review already exists
        $reviewModel = \App\Models\Product\ProductReview::where('user_id', $user_id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($reviewModel && $reviewModel->status === 'approved') {
            // Check latest order date to allow edit if recently ordered
            $latestOrder = \App\Models\OrderItem::where('product_id', $request->product_id)
                ->whereHas('order', function($q) use ($user_id) {
                    $q->where('user_id', $user_id)->where('status', 'delivered');
                })
                ->with('order')
                ->get()
                ->sortByDesc(function ($item) {
                    return $item->order->updated_at ?? $item->created_at;
                })->first();

            $latestOrderDate = $latestOrder ? ($latestOrder->order->updated_at ?? $latestOrder->created_at) : null;

            if (!$latestOrderDate || $latestOrderDate <= $reviewModel->updated_at) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'This review has already been published and cannot be edited until you purchase again.'], 403);
                }
                return back()->with('error', 'This review has already been published and cannot be edited until you purchase again.');
            }
        }

        $existingAttachments = [];
        if ($reviewModel && is_array($reviewModel->attachments)) {
            $existingAttachments = $reviewModel->attachments;
        }

        $newAttachmentsCount = $request->hasFile('attachments') ? count($request->file('attachments')) : 0;
        $totalAttachments = count($existingAttachments) + $newAttachmentsCount;

        if ($totalAttachments > 3) {
            $allowedNew = 3 - count($existingAttachments);
            $msg = $allowedNew > 0 
                ? "You can only upload $allowedNew more photo(s). Maximum 3 photos allowed per review." 
                : "You have already uploaded the maximum of 3 photos.";
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        $attachments = $existingAttachments;
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('reviews', 'public');
                $attachments[] = $path;
            }
        }

        // Create or update review
        \App\Models\Product\ProductReview::updateOrCreate(
            ['user_id' => $user_id, 'product_id' => $request->product_id],
            [
                'rating' => $request->rating,
                'review' => $request->review,
                'attachments' => count($attachments) > 0 ? $attachments : null,
                'status' => 'pending' // Always pending for admin approval
            ]
        );

        if ($request->wantsJson() || $request->ajax()) {
            // Flash success message to session so it shows up on redirect
            session()->flash('success', 'Your review has been submitted and is pending approval.');
            return response()->json(['success' => true, 'redirect' => route('dashboard.reviews')]);
        }

        return redirect()->route('dashboard.reviews')->with('success', 'Your review has been submitted and is pending approval.');
    }

    public function profile()
    {
        return view('dashboard', ['activeTab' => 'profile']);
    }

    public function updateProfile(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $rules = [
            'name' => 'required|string|max:20',
        ];

        if ($request->has('phone') && !empty($user->google_id)) {
            $rules['phone'] = 'nullable|string|max:20|unique:users,phone,' . $user->id;
        }

        $request->validate($rules);

        $user->name = $request->name;

        if ($request->has('phone') && !empty($user->google_id)) {
            $user->phone = $request->phone;
        }

        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Profile updated successfully.']);
        }
        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');

            // Delete old avatar if it exists and is not a social URL
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = '/storage/' . $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'avatar_url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No image uploaded.'], 400);
    }

    public function updatePassword(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $rules = [
            'password' => 'required|string|min:6|confirmed',
        ];

        if (empty($user->google_id)) {
            $rules['current_password'] = 'required|current_password';
        }

        $messages = [
            'current_password.required' => 'Please enter your current password.',
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.required' => 'Please enter a new password.',
            'password.min' => 'New password must be at least 6 characters.',
            'password.confirmed' => 'New password confirmation does not match.',
        ];

        $request->validate($rules, $messages);

        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        // Send security alert email if enabled
        try {
            if (\App\Services\MailConfigService::isMailEnabled() && !empty($user->email)) {
                \App\Services\MailConfigService::applyRuntimeConfig();
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\PasswordChangedMail($user, [
                    'changed_at' => now()->format('d M Y, h:i:s A'),
                    'ip' => $request->ip(),
                    'device' => $request->header('User-Agent') ? (preg_match('/Mobi|Android|iPhone/i', $request->header('User-Agent')) ? 'Mobile Device' : 'Desktop Browser') : 'Web',
                ]));

                \App\Models\EmailLog::record(
                    $user->email,
                    'Security Alert: Your Password Was Changed - ' . \App\Models\Setting::get('site_name', 'Ario Mart'),
                    'password_changed',
                    'sent',
                    null,
                    $user->name,
                    $user->id,
                    null,
                    'system'
                );
            }
        } catch (\Throwable $mailEx) {
            \Illuminate\Support\Facades\Log::warning('Password Change Alert Mail Error: ' . $mailEx->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your password has been updated successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Your password has been updated successfully.');
    }

    public function showOrder($order_id)
    {
        $order = \App\Models\Order::with('items')->where('order_id', $order_id)->firstOrFail();
        return view('frontend.dashboard.order-details', compact('order'));
    }

    public function invoice($order_id)
    {
        $order = \App\Models\Order::with('items')->where('order_id', $order_id)->firstOrFail();
        return view('frontend.dashboard.invoice', compact('order'));
    }
}
