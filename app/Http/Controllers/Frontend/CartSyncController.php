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
     * Update customer contact info when they start typing in checkout.
     */
    public function updateCustomerInfo(Request $request): JsonResponse
    {
        $visitorToken = trim((string) $request->input('visitor_token', ''));
        if (empty($visitorToken)) {
            return response()->json(['success' => false], 422);
        }

        $name = trim((string) $request->input('name', ''));
        $phone = trim((string) $request->input('phone', ''));

        $update = [];
        if (!empty($name)) {
            $update['customer_name'] = $name;
        }
        if (!empty($phone)) {
            $update['customer_phone'] = $phone;
        }

        if (!empty($update)) {
            CartSession::where('visitor_token', $visitorToken)
                ->where('status', 'active')
                ->latest('id')
                ->first()
                ?->update($update);
        }

        return response()->json(['success' => true]);
    }
}
