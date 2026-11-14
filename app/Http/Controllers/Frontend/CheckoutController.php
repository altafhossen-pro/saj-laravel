<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product\Product;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmationMail;
use Exception;

class CheckoutController extends Controller
{
    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'delivery_type' => 'nullable|in:inside,sub,outside',
            'items' => 'required|array|min:1',
            'phone' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide coupon code and cart items.'
            ], 422);
        }

        $code = strtoupper(trim($request->coupon_code));
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code.'
            ], 404);
        }

        $userId = Auth::id();
        $phone = $request->phone;

        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += ((float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1));
        }

        $deliveryCharges = [
            'inside' => (float) \App\Models\Setting::get('delivery_charge_inside_dhaka', 80),
            'sub' => (float) \App\Models\Setting::get('delivery_charge_sub_dhaka', 120),
            'outside' => (float) \App\Models\Setting::get('delivery_charge_outside_dhaka', 150),
            'free_min_order' => (float) \App\Models\Setting::get('free_delivery_min_order', 3000),
        ];

        $deliveryType = $request->delivery_type ?? 'inside';
        $shippingCost = $deliveryCharges[$deliveryType] ?? 80;
        if ($subtotal >= $deliveryCharges['free_min_order']) {
            $shippingCost = 0;
        }

        $result = $coupon->calculateDiscount($request->items, $subtotal, $shippingCost, $userId, $phone);

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'coupon_code' => $coupon->code,
            'discount' => (float) round($result['discount']),
            'discount_type' => $result['discount_type'],
            'discount_amount' => $result['discount_amount'],
        ]);
    }

    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'delivery_type' => 'required|in:inside,sub,outside',
            'payment_method' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'coupon_code' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $isGuest = !$user;
        $data = $request->all();

        // 1. Coupon lookup
        $coupon = null;
        if (!empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', strtoupper(trim($data['coupon_code'])))->first();
            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired coupon code.'
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $totalSubtotal = 0;
            $orderItemsData = [];
            $stockReductions = [];

            // 2. Validate Items, Stock with Pessimistic Lock, and Prices
            foreach ($data['items'] as $index => $item) {
                // Lock product row for atomic race-condition safety
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product) {
                    throw new Exception("দুঃখিত! পণ্যটি খুঁজে পাওয়া যায়নি।");
                }

                // Check active status & force out of stock
                if (!$product->is_active || $product->is_force_out_of_stock) {
                    throw new Exception("দুঃখিত! '{$product->title}' পণ্যটি বর্তমানে আর উপলব্ধ নেই।");
                }

                $variant = null;
                if (!empty($item['variant_id'])) {
                    $variant = \App\Models\Product\ProductVariant::lockForUpdate()->find($item['variant_id']);
                } elseif (!empty($item['variant_sku'])) {
                    $variant = \App\Models\Product\ProductVariant::lockForUpdate()
                                ->where('product_id', $product->id)
                                ->where('sku', $item['variant_sku'])->first();
                }

                if ($variant) {
                    $actualPrice = (float) ($variant->sale_price > 0 ? $variant->sale_price : $variant->current_price);
                    $stock = (int) $variant->stock_quantity;
                } else {
                    $actualPrice = (float) $product->base_price;
                    $stock = (int) $product->total_stock;
                }
                
                // Real-time stock validation (Race condition catch)
                if ($stock <= 0) {
                    throw new Exception("দুঃখিত! আপনি অর্ডার সম্পন্ন করার মুহূর্তেই অন্য একজন গ্রাহক '{$product->title}'-এর শেষ স্টকটি অর্ডার করে ফেলেছেন। পণ্যটি এইমাত্র স্টক আউট হয়েছে।");
                }

                if ($stock < $item['quantity']) {
                    throw new Exception("দুঃখিত! '{$product->title}'-এর আর মাত্র {$stock}টি অবশিষ্ট আছে। অনুগ্রহ করে পরিমাণ কমিয়ে আবার চেষ্টা করুন।");
                }

                // Price validation
                $frontendPrice = (float) str_replace(',', '', $item['price']);
                $backendPrice = (float) $actualPrice;
                
                if (abs($frontendPrice - $backendPrice) > 0.05) {
                    throw new Exception("মূল্য পরিবর্তন হয়েছে: '{$product->title}'-এর পূর্বের মূল্য ছিল ৳{$frontendPrice}, বর্তমান মূল্য ৳{$backendPrice}। আপনার কার্ট স্বয়ংক্রিয়ভাবে আপডেট করা হয়েছে, অনুগ্রহ করে আবার কনফার্ম করুন।");
                }

                $itemSubtotal = $actualPrice * $item['quantity'];
                $totalSubtotal += $itemSubtotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'name' => $product->title,
                    'image' => $product->featured_image ?? null,
                    'price' => $actualPrice,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                    'variant_sku' => $variant ? $variant->sku : ($item['variant_sku'] ?? null),
                    'variant_info' => [
                        'label' => $item['variant_label'] ?? null,
                        'attributes' => $variant ? $variant->attributes : null,
                    ]
                ];

                $stockReductions[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $item['quantity']
                ];
            }

            // Calculate Shipping
            $deliveryCharges = \Illuminate\Support\Facades\Cache::rememberForever('delivery_charges', function () {
                return [
                    'inside' => (int) \App\Models\Setting::get('delivery_charge_inside_dhaka', 70),
                    'sub' => (int) \App\Models\Setting::get('delivery_charge_sub_dhaka', 110),
                    'outside' => (int) \App\Models\Setting::get('delivery_charge_outside_dhaka', 130),
                    'free_min_order' => (int) \App\Models\Setting::get('delivery_free_min_order', 3000),
                ];
            });

            $shippingCost = 0;
            if ($totalSubtotal >= ($deliveryCharges['free_min_order'] ?? 3000)) {
                $shippingCost = 0;
            } else if (isset($deliveryCharges[$data['delivery_type']])) {
                $shippingCost = $deliveryCharges[$data['delivery_type']];
            }

            $couponDiscount = 0;
            if ($coupon) {
                $couponResult = $coupon->calculateDiscount(
                    $data['items'],
                    $totalSubtotal,
                    $shippingCost,
                    $user?->id,
                    $data['phone'] ?? null
                );

                if (!$couponResult['valid']) {
                    throw new Exception($couponResult['message']);
                }

                $couponDiscount = (float) round($couponResult['discount']);
            }

            $total = max(0, (float) round($totalSubtotal + $shippingCost - $couponDiscount));

            // 3. Create Order
            $order = new Order();
            if (!$isGuest) {
                $order->user_id = $user->id;
            }
            $order->is_guest_order = $isGuest;
            $order->ip_address = $request->ip();
            $order->order_source = 'website';
            $order->order_type = 'auto';

            // Guest or manual info + Meta Pixel/CAPI attribution data
            $metaAttribution = [
                'fbp' => $request->cookie('_fbp'),
                'fbc' => $request->cookie('_fbc'),
                'client_ip_address' => $request->ip(),
                'client_user_agent' => $request->userAgent(),
                'event_source_url' => $request->headers->get('referer') ?: url('/checkout'),
            ];

            $customerInfo = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'meta_attribution' => $metaAttribution,
            ];

            // Always store customer & meta attribution in guest_info array
            $order->guest_info = $customerInfo;
            // For Laravel, the schema has shipping_address JSON
            $order->shipping_address = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
            ];
            $order->billing_address = $order->shipping_address;

            $order->status = 'pending';
            $order->is_stock_deducted = false;
            $order->payment_method = $data['payment_method'];
            $order->payment_status = 'pending';
            
            $order->total = $total;
            $order->shipping_cost = $shippingCost;
            $order->discount = 0;
            $order->coupon = $coupon ? $coupon->code : null;
            $order->coupon_discount = $couponDiscount;
            
            $order->order_notes = $data['notes'] ?? null;
            $order->status_timestamps = ['pending' => now()];

            $order->save();

            // Record coupon usage
            if ($coupon && $couponDiscount > 0) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user?->id,
                    'order_id' => $order->id,
                    'customer_phone' => $data['phone'] ?? null,
                    'discount_applied' => $couponDiscount,
                ]);

                $coupon->increment('used_count');
            }

            // 4. Save Order Items
            // Note: Stock is NOT deducted on Pending checkout. Stock deduction is triggered when order is Confirmed.
            foreach ($orderItemsData as $itemData) {
                $orderItem = new OrderItem($itemData);
                $order->items()->save($orderItem);
            }

            DB::commit();

            // Send Order Confirmation Email if user is logged in and template is enabled
            try {
                $isEmailActive = (bool) \App\Models\Setting::get('email_tpl_order_placed_active', 1);
                $customerEmail = null;

                if (!$isGuest && $user && !empty($user->email)) {
                    $customerEmail = $user->email;
                } elseif (!empty($request->input('email'))) {
                    $customerEmail = $request->input('email');
                }

                if (\App\Services\MailConfigService::isMailEnabled() && $isEmailActive && $customerEmail) {
                    \App\Services\MailConfigService::applyRuntimeConfig();
                    $order->loadMissing(['items', 'user']);
                    Mail::to($customerEmail)->send(new OrderConfirmationMail($order));

                    \App\Models\EmailLog::record(
                        $customerEmail,
                        'Order Confirmed! Your Order #' . $order->order_id . ' - ' . \App\Models\Setting::get('site_name', 'Ario Mart'),
                        'order_placed',
                        'sent',
                        null,
                        $data['name'] ?? optional($user)->name,
                        optional($user)->id,
                        $order->id,
                        'system'
                    );
                }
            } catch (\Throwable $mailEx) {
                \Illuminate\Support\Facades\Log::warning("Order Confirmation Mail Error: " . $mailEx->getMessage(), [
                    'order_id' => $order->order_id
                ]);

                if (!empty($customerEmail)) {
                    \App\Models\EmailLog::record(
                        $customerEmail,
                        'Order Confirmed! Your Order #' . $order->order_id,
                        'order_placed',
                        'failed',
                        $mailEx->getMessage(),
                        $data['name'] ?? null,
                        optional($user)->id,
                        $order->id,
                        'system'
                    );
                }
            }

            // Mark Cart Session as Converted
            try {
                $visitorToken = trim((string) $request->input('visitor_token', ''));
                if (!empty($visitorToken)) {
                    \App\Models\CartSession::where('visitor_token', $visitorToken)
                        ->where('status', '!=', 'converted')
                        ->update([
                            'status' => 'converted',
                            'order_id' => $order->id,
                            'customer_name' => $data['name'],
                            'customer_phone' => $data['phone'],
                        ]);
                } elseif ($user) {
                    \App\Models\CartSession::where('user_id', $user->id)
                        ->where('status', '!=', 'converted')
                        ->latest('id')
                        ->first()
                        ?->update([
                            'status' => 'converted',
                            'order_id' => $order->id,
                            'customer_name' => $data['name'],
                            'customer_phone' => $data['phone'],
                        ]);
                }
            } catch (\Throwable $cartEx) {
                \Illuminate\Support\Facades\Log::warning("CartSession conversion error: " . $cartEx->getMessage());
            }

            // Note: Meta CAPI Purchase is delayed until order confirmation by Admin to filter out fake/cancelled COD orders.
            // When status is changed to 'confirmed' in Admin Panel, FacebookCapiService::trackPurchase() fires automatically.

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'order_id' => $order->order_id
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::warning("Checkout Notice / Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function success($order_id)
    {
        $order = Order::with('items.product')->where('order_id', $order_id)->firstOrFail();
        
        return view('frontend.checkout-success', compact('order'));
    }
}
