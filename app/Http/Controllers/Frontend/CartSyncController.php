<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CartSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartSyncController extends Controller
{
    /**
     * Synchronize cart items and user/guest state seamlessly in background.
     */
    public function sync(Request $request): JsonResponse
    {
        $visitorToken = trim((string) $request->input('visitor_token', ''));
        if (empty($visitorToken) || strlen($visitorToken) < 6) {
            return response()->json(['success' => false, 'message' => 'Invalid visitor token'], 422);
        }

        $items = $request->input('items', []);
        if (!is_array($items)) {
            $items = [];
        }

        $user = Auth::user();
        $userId = $user?->id;
        $customerName = $request->input('customer_name') ?: $user?->name;
        $customerPhone = $request->input('customer_phone') ?: $user?->phone;

        // If items are empty, clear active session items
        if (empty($items)) {
            CartSession::where('visitor_token', $visitorToken)
                ->where('status', 'active')
                ->update([
                    'items' => [],
                    'total_items' => 0,
                    'total_amount' => 0,
                    'last_activity_at' => now(),
                ]);

            return response()->json(['success' => true, 'status' => 'cleared']);
        }

        // Sanitize and format items
        $sanitizedItems = [];
        $totalItems = 0;
        $totalAmount = 0.0;

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $price = (float) ($item['price'] ?? 0);
            $totalItems += $qty;
            $totalAmount += ($price * $qty);

            $sanitizedItems[] = [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'variant_id' => !empty($item['variant_id']) ? (int) $item['variant_id'] : null,
                'name' => (string) ($item['name'] ?? 'Product'),
                'price' => $price,
                'quantity' => $qty,
                'image' => (string) ($item['image'] ?? ''),
                'variant_name' => (string) ($item['variant_name'] ?? ''),
            ];
        }

        // Look for existing active cart session for this visitor
        $cartSession = CartSession::where('visitor_token', $visitorToken)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $dataToUpdate = [
            'visitor_token' => $visitorToken,
            'user_id' => $userId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'items' => $sanitizedItems,
            'total_items' => $totalItems,
            'total_amount' => $totalAmount,
            'status' => 'active',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'last_activity_at' => now(),
        ];

        if ($cartSession) {
            $cartSession->update($dataToUpdate);
        } else {
            CartSession::create($dataToUpdate);
        }

        return response()->json([
            'success' => true,
            'total_items' => $totalItems,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Real-time stock & price validation endpoint for Cart Drawer & Checkout.
     * Keeps prices transparent, syncs latest database prices, and flags out-of-stock items.
     */
    public function validateCart(Request $request): JsonResponse
    {
        $items = $request->input('items', []);
        if (!is_array($items) || empty($items)) {
            return response()->json([
                'success' => true,
                'items' => [],
                'changes' => [],
                'has_out_of_stock' => false
            ]);
        }

        $validatedItems = [];
        $changes = [];
        $hasOutOfStock = false;

        $productIds = array_filter(array_column($items, 'product_id'));
        $variantIds = array_filter(array_column($items, 'variant_id'));

        $products = \App\Models\Product\Product::whereIn('id', $productIds)->get()->keyBy('id');
        $variants = !empty($variantIds)
            ? \App\Models\Product\ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        foreach ($items as $item) {
            $pId = (int) ($item['product_id'] ?? 0);
            $vId = !empty($item['variant_id']) ? (int) $item['variant_id'] : null;

            $product = $products->get($pId);
            if (!$product || !$product->is_active) {
                $hasOutOfStock = true;
                $item['is_out_of_stock'] = true;
                $item['stock_available'] = 0;
                $item['message'] = "পণ্যটি বর্তমানে আর উপলব্ধ নেই।";
                $changes[] = [
                    'type' => 'unavailable',
                    'title' => $item['title'] ?? ($item['name'] ?? 'Product'),
                    'message' => "'" . ($item['title'] ?? 'পণ্য') . "' বর্তমানে উপলব্ধ নেই।"
                ];
                $validatedItems[] = $item;
                continue;
            }

            $variant = $vId ? $variants->get($vId) : null;

            if ($variant) {
                $actualPrice = (float) ($variant->sale_price > 0 ? $variant->sale_price : $variant->current_price);
                $stock = (int) $variant->stock_quantity;
            } else {
                $actualPrice = (float) $product->base_price;
                $stock = (int) $product->total_stock;
            }

            $currentCartPrice = (float) ($item['price'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));

            // Price change detection
            if (abs($currentCartPrice - $actualPrice) >= 0.01) {
                $diffText = $actualPrice > $currentCartPrice ? 'বৃদ্ধি' : 'হ্রাস';
                $changes[] = [
                    'type' => 'price_changed',
                    'product_id' => $pId,
                    'variant_id' => $vId,
                    'title' => $product->title,
                    'old_price' => $currentCartPrice,
                    'new_price' => $actualPrice,
                    'message' => "{$product->title}-এর পূর্বের মূল্য ছিল ৳{$currentCartPrice}। সাম্প্রতিক মূল্যায়নে বর্তমান মূল্য ৳{$actualPrice} নির্ধারণ করা হয়েছে।"
                ];
                $item['price'] = $actualPrice;
                $item['price_updated'] = true;
                $item['old_price'] = $currentCartPrice;
            }

            // Stock availability check
            if ($stock <= 0) {
                $hasOutOfStock = true;
                $item['is_out_of_stock'] = true;
                $item['stock_available'] = 0;
                $changes[] = [
                    'type' => 'stock_out',
                    'product_id' => $pId,
                    'variant_id' => $vId,
                    'title' => $product->title,
                    'message' => "দুঃখিত! '{$product->title}' এইমাত্র স্টক আউট হয়েছে।"
                ];
            } else {
                $item['is_out_of_stock'] = false;
                $item['stock_available'] = $stock;
                if ($qty > $stock) {
                    $item['quantity'] = $stock;
                    $changes[] = [
                        'type' => 'stock_limited',
                        'product_id' => $pId,
                        'variant_id' => $vId,
                        'title' => $product->title,
                        'message' => "'{$product->title}'-এর সর্বোচ্চ {$stock}টি অবশিষ্ট আছে, কার্ট এডজাস্ট করা হয়েছে।"
                    ];
                }
            }

            $item['max_stock'] = $stock;
            $validatedItems[] = $item;
        }

        return response()->json([
            'success' => true,
            'items' => $validatedItems,
            'changes' => $changes,
            'has_out_of_stock' => $hasOutOfStock
        ]);
    }

    /**
     * Update customer contact info when they start typing in checkout.
     */
    public function updateCustomerInfo(Request $request): JsonResponse
    {
        $visitorToken = trim((string) $request->input('visitor_token', ''));
        if (empty($visitorToken)) {
            return response()->json(['success' => false], 422);
        }

        $name = trim((string) $request->input('name', ''));
        $rawPhone = trim((string) $request->input('phone', ''));
        $address = trim((string) $request->input('address', ''));

        // Clean & validate BD phone number: must be 11 digits starting with 01
        $phone = '';
        if (!empty($rawPhone)) {
            $cleaned = preg_replace('/[^\d+]/', '', $rawPhone);
            if (str_starts_with($cleaned, '+880')) {
                $cleaned = '0' . substr($cleaned, 4);
            } elseif (str_starts_with($cleaned, '+88')) {
                $cleaned = substr($cleaned, 3);
                if (!str_starts_with($cleaned, '0')) $cleaned = '0' . $cleaned;
            } elseif (str_starts_with($cleaned, '880')) {
                $cleaned = '0' . substr($cleaned, 3);
            } elseif (str_starts_with($cleaned, '+')) {
                $cleaned = ltrim($cleaned, '+');
            }
            $cleaned = preg_replace('/\D/', '', $cleaned);
            if (str_starts_with($cleaned, '880')) {
                $cleaned = '0' . substr($cleaned, 3);
            }
            $cleaned = substr($cleaned, 0, 11);

            if (preg_match('/^01[3-9]\d{8}$/', $cleaned)) {
                $phone = $cleaned;
            }
        }

        $update = [];
        if (!empty($name)) {
            $update['customer_name'] = $name;
        }
        if (!empty($phone)) {
            $update['customer_phone'] = $phone;
        }
        if (!empty($address)) {
            $update['customer_address'] = $address;
        }

        if (!empty($update)) {
            // Find active CartSession
            $cartSession = CartSession::where('visitor_token', $visitorToken)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if ($cartSession) {
                // Keep history of previous revisions so admin can see and choose any prior version
                $history = is_array($cartSession->info_history) ? $cartSession->info_history : [];
                $last = !empty($history) ? end($history) : null;
                $changed = !$last ||
                    (!empty($name) && $name !== ($last['name'] ?? '')) ||
                    (!empty($phone) && $phone !== ($last['phone'] ?? '')) ||
                    (!empty($address) && $address !== ($last['address'] ?? ''));

                if ($changed) {
                    $history[] = [
                        'version' => count($history) + 1,
                        'name' => $name ?: ($last['name'] ?? $cartSession->customer_name),
                        'phone' => $phone ?: ($last['phone'] ?? $cartSession->customer_phone),
                        'address' => $address ?: ($last['address'] ?? $cartSession->customer_address),
                        'time' => now()->format('h:i:s A (d M)'),
                        'timestamp' => now()->timestamp,
                    ];
                    // Keep max 15 versions to preserve performance
                    if (count($history) > 15) {
                        $history = array_slice($history, -15);
                    }
                    $update['info_history'] = $history;
                }

                $cartSession->update($update);
            }

            // Also synchronize with today's VisitorSession
            \App\Models\VisitorSession::where('visitor_token', $visitorToken)
                ->where('session_date', date('Y-m-d'))
                ->latest('id')
                ->first()
                ?->update($update);

            // Synchronize with RAM active sessions so live monitor sees customer info immediately
            foreach (['/dev/shm/ariomart_visitors', storage_path('framework/cache/visitors')] as $dir) {
                $activeFile = $dir . '/live_sessions.json';
                if (file_exists($activeFile)) {
                    $content = @file_get_contents($activeFile);
                    $sessions = json_decode($content, true);
                    if (is_array($sessions) && isset($sessions[$visitorToken])) {
                        if (!empty($name)) $sessions[$visitorToken]['name'] = $name;
                        if (!empty($phone)) $sessions[$visitorToken]['phone'] = $phone;
                        if (!empty($address)) $sessions[$visitorToken]['address'] = $address;
                        if (!empty($name)) $sessions[$visitorToken]['last_seen_as'] = $name;
                        if (!empty($update['info_history'])) {
                            $sessions[$visitorToken]['info_history'] = $update['info_history'];
                        }
                        @file_put_contents($activeFile, json_encode($sessions));
                        break;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'phone_valid' => !empty($phone),
            'formatted_phone' => $phone
        ]);
    }
}
