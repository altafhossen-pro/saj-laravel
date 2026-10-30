<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Services\SteadfastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', ['activeTab' => 'all-orders']);
    }

    public function manual()
    {
        return view('admin.orders.manual');
    }

    public function drafts()
    {
        // For now just redirecting to index with a parameter, or a different view later
        return view('admin.dashboard', ['activeTab' => 'draft-orders']);
    }

    public function searchCustomer(Request $request)
    {
        $phone = $request->query('phone');
        if (!$phone) {
            return response()->json(['found' => false]);
        }

        // Search for previous order by this phone to extract info
        $order = Order::where('shipping_address->phone', $phone)
            ->orderBy('id', 'desc')
            ->first();

        if ($order && $order->shipping_address) {
            $address = is_string($order->shipping_address) ? json_decode($order->shipping_address, true) : $order->shipping_address;
            return response()->json([
                'found' => true,
                'name' => $address['name'] ?? '',
                'address' => $address['address'] ?? '',
            ]);
        }

        // Alternatively check users table if needed
        $user = \App\Models\User::where('phone', $phone)->first();
        if ($user) {
            return response()->json([
                'found' => true,
                'name' => $user->name,
                'address' => $user->address ?? '',
            ]);
        }

        return response()->json(['found' => false]);
    }
        
    public function storeManual(Request $request)
    {
        $request->validate([
            'customer_contact' => 'required|string',
            'customer_name' => 'required|string',
            'shipping_address' => 'required|string',
            'items' => 'required|array|min:1',
            'is_draft' => 'boolean'
        ]);

        $is_draft = $request->is_draft ?? false;
        
        $shipping = [
            'name' => $request->customer_name,
            'phone' => $request->customer_contact,
            'address' => $request->shipping_address,
        ];

        $order = Order::create([
            'total' => $request->total ?? 0,
            'subtotal' => $request->subtotal ?? 0,
            'shipping_cost' => $request->delivery_charge ?? 0,
            'discount' => $request->discount ?? 0,
            'status' => $is_draft ? 'draft' : 'pending',
            'payment_method' => 'cod', 
            'payment_status' => 'pending',
            'shipping_address' => $shipping,
            'order_notes' => $request->notes,
            'order_source' => $request->source ?? 'Manual',
        ]);

        foreach ($request->items as $item) {
            \App\Models\Order\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'variant_sku' => $item['variant_sku'] ?? $item['product_sku'] ?? null,
                'image' => $item['image'] ?? null,
                'quantity' => $item['qty'],
                'price' => $item['original_price'] ?? $item['price'],
                'subtotal' => $item['qty'] * ($item['original_price'] ?? $item['price']),
                'variant_info' => !empty($item['variantLabel']) ? ['label' => $item['variantLabel']] : null,
            ]);
        }

        \App\Models\Order\OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'Order created via POS ' . ($is_draft ? '(Draft)' : ''),
            'old_values' => null,
            'new_values' => ['status' => $is_draft ? 'draft' : 'pending']
        ]);

        session()->flash('success', $is_draft ? 'Draft saved successfully!' : 'Order created successfully!');

        return response()->json([
            'success' => true,
            'redirect' => $is_draft ? route('admin.orders.drafts') : route('admin.orders.show', $order->order_id)
        ]);
    }


    public function show($order_id)
    {
        $order = Order::with(['items.product.variants', 'user'])->where('order_id', $order_id)->firstOrFail();
        return view('admin.orders.show', compact('order'));
    }

    public function fetchOrders(Request $request)
    {
        $query = Order::query()->with(['items', 'user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('shipping_address->name', 'like', "%{$search}%")
                  ->orWhere('shipping_address->phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Date Filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $allowedSorts = ['created_at', 'total', 'order_id'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->input('per_page', 10);
        $orders = $query->paginate($perPage);

        // Map for JSON response
        $mappedOrders = $orders->map(function($order) {
            $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
            $customerName = $shippingAddress['name'] ?? ($order->user->name ?? 'Guest');
            $customerPhone = $shippingAddress['phone'] ?? ($order->user->phone ?? '');
            
            // Order Count for this phone
            $orderCount = 1;
            if ($customerPhone) {
                $orderCount = \App\Models\Order\Order::where(function($q) use ($customerPhone) {
                    $q->where('shipping_address->phone', $customerPhone)
                      ->orWhereHas('user', function($u) use ($customerPhone) {
                          $u->where('phone', $customerPhone);
                      });
                })->count();
            }

            $userId = $order->user_id;
            if (!$userId && $customerPhone) {
                $matchedUser = \App\Models\User::where('phone', $customerPhone)->first();
                if ($matchedUser) {
                    $userId = $matchedUser->id;
                }
            }

            return [
                'id' => $order->id, // Internal ID, maybe keep it but don't show in URL
                'order_id' => $order->order_id, // This is the string/unique ID we will use in URLs
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_type' => $userId ? 'Registered' : 'Guest',
                'user_id' => $userId,
                'order_count' => $orderCount,
                'date' => $order->created_at ? $order->created_at->format('M d, Y h:i A') : '',
                'status' => $order->status,
                'payment_status' => $order->payment_status ?? 'pending',
                'source' => $order->order_source ?? 'website',
                'discount' => number_format((float) $order->total_discount, 2),
                'raw_discount' => (float) $order->total_discount,
                'coupon' => $order->coupon,
                'coupon_discount' => (float) ($order->coupon_discount ?? 0),
                'items_count' => $order->items ? $order->items->count() : 0,
                'total_quantity' => $order->items ? $order->items->sum('quantity') : 0,
                'total' => number_format((float) $order->total, 2),
            ];
        });

        return response()->json([
            'data' => $mappedOrders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ]
        ]);
    }

    public function updateStatus(Request $request, $order_id, SteadfastService $steadfastService)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,returned',
            'send_to_steadfast' => 'nullable|boolean',
        ]);

        $order = Order::where('order_id', $order_id)->firstOrFail();
        
        $oldStatus = $order->status;
        $newStatus = $request->status;
        $steadfastBooked = false;
        $consignmentData = null;

        // If admin requested adding to Steadfast (only allowed if order is in processing status)
        if ($request->boolean('send_to_steadfast')) {
            if ($order->status !== 'processing') {
                return response()->json([
                    'message' => 'Order must be in processing status before sending to Steadfast.',
                ], 422);
            }

            if (!$order->is_added_into_steadfast) {
                $steadfastRes = $steadfastService->createOrder($order);
                if (!$steadfastRes['success']) {
                    return response()->json([
                        'message' => 'Steadfast Booking Failed: ' . $steadfastRes['message'],
                        'errors' => $steadfastRes['errors'] ?? null,
                    ], 422);
                }
                $steadfastBooked = true;
                $consignmentData = $steadfastRes['consignment'] ?? null;
                $order->refresh();
            }
        }

        if ($oldStatus !== $newStatus) {
            $order->status = $newStatus;
            
            // Add to status timestamps
            $timestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];
            $timestamps[$newStatus] = now()->toDateTimeString();
            $order->status_timestamps = $timestamps;
            
            // Add to update history
            $history = is_array($order->update_history) ? $order->update_history : [];
            $history[] = [
                'action' => 'status_updated',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'steadfast_booked' => $steadfastBooked,
                'updated_by' => auth()->id() ?? 0,
                'timestamp' => now()->toDateTimeString()
            ];
            $order->update_history = $history;

            $order->save();

            // Trigger Delivered Email if status changed to 'delivered'
            if ($newStatus === 'delivered' && \App\Services\MailConfigService::isMailEnabled() && \App\Models\Setting::get('email_tpl_order_delivered_active', 1)) {
                try {
                    $shipping = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address, true);
                    $customerEmail = optional($order->user)->email ?? ($shipping['email'] ?? null);

                    if ($customerEmail) {
                        \App\Services\MailConfigService::applyRuntimeConfig();
                        \Illuminate\Support\Facades\Mail::to($customerEmail)->send(new \App\Mail\OrderDeliveredMail($order));
                        \App\Models\EmailLog::record(
                            $customerEmail,
                            'Your Order #' . $order->order_id . ' Has Been Delivered! 🎉 - ' . \App\Models\Setting::get('site_name', 'Ario Mart'),
                            'order_delivered',
                            'sent',
                            null,
                            $shipping['name'] ?? optional($order->user)->name,
                            optional($order->user)->id,
                            $order->id,
                            'system'
                        );
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Order Delivered Mail Error: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'message' => $steadfastBooked 
                ? 'Status updated to ' . ucfirst($newStatus) . ' and parcel booked in Steadfast successfully!' 
                : 'Status updated successfully',
            'status' => $newStatus,
            'steadfast_booked' => $steadfastBooked,
            'is_added_into_steadfast' => (bool) $order->is_added_into_steadfast,
            'steadfast_consignment_id' => $order->steadfast_consignment_id,
            'steadfast_tracking_code' => $order->steadfast_tracking_code,
            'consignment' => $consignmentData,
        ]);
    }

    public function sendToSteadfast(Request $request, $order_id, SteadfastService $steadfastService)
    {
        $order = Order::where('order_id', $order_id)->firstOrFail();

        if ($order->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Order must be in processing status before booking into Steadfast.',
            ], 422);
        }

        if ($order->is_added_into_steadfast) {
            return response()->json([
                'success' => false,
                'message' => 'This order is already booked in Steadfast (CID: #' . $order->steadfast_consignment_id . ').',
            ], 422);
        }

        $result = $steadfastService->createOrder($order);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Steadfast Booking Failed: ' . $result['message'],
                'errors' => $result['errors'] ?? null,
            ], 422);
        }

        // Auto update status to shipped if it was pending / confirmed / processing
        if (in_array($order->status, ['pending', 'confirmed', 'processing'])) {
            $order->status = 'shipped';
            $timestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];
            $timestamps['shipped'] = now()->toDateTimeString();
            $order->status_timestamps = $timestamps;

            $history = is_array($order->update_history) ? $order->update_history : [];
            $history[] = [
                'action' => 'status_updated',
                'old_status' => $order->getOriginal('status'),
                'new_status' => 'shipped',
                'note' => 'Auto updated to shipped upon Steadfast booking',
                'updated_by' => auth()->id() ?? 0,
                'timestamp' => now()->toDateTimeString()
            ];
            $order->update_history = $history;
            $order->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Order booked into Steadfast successfully!',
            'consignment' => $result['consignment'] ?? null,
            'consignment_id' => $result['consignment_id'] ?? null,
            'tracking_code' => $result['tracking_code'] ?? null,
            'tracking_link' => $result['tracking_link'] ?? null,
            'status' => $order->status,
        ]);
    }

    public function getSteadfastStatus($order_id, SteadfastService $steadfastService)
    {
        $order = Order::where('order_id', $order_id)->firstOrFail();
        if (!$order->steadfast_consignment_id && !$order->steadfast_tracking_code) {
            return response()->json([
                'success' => false,
                'message' => 'No Steadfast booking exists for this order.'
            ], 404);
        }

        if ($order->steadfast_consignment_id) {
            $result = $steadfastService->getDeliveryStatusByConsignmentId($order->steadfast_consignment_id);
        } else {
            $result = $steadfastService->getDeliveryStatusByTrackingCode($order->steadfast_tracking_code);
        }

        return response()->json($result);
    }

    public function checkSteadfastFraud(Request $request, SteadfastService $steadfastService)
    {
        $phone = $request->query('phone');
        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Phone number is required.'], 422);
        }

        $result = $steadfastService->checkFraud($phone);
        return response()->json($result);
    }

    public function edit($order_id)
    {
        $order = Order::with(['items.product.variants', 'user'])->where('order_id', $order_id)->firstOrFail();
        return view('admin.orders.edit', compact('order'));
    }

    public function update(Request $request, $order_id)
    {
        $order = Order::with('items')->where('order_id', $order_id)->firstOrFail();

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,returned',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string|max:100',
            'order_source' => 'required|in:website,facebook,whatsapp,phone,email,walk-in,instagram,manual,other',

            'shipping.name' => 'required|string|max:255',
            'shipping.phone' => 'required|string|max:20',
            'shipping.address' => 'required|string',
            'shipping.city' => 'nullable|string|max:255',
            'shipping.zone' => 'nullable|string|max:255',

            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',

            'discount' => 'nullable|numeric|min:0',
            'coupon_discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',

            'order_notes' => 'nullable|string',
            'admin_notes' => 'nullable|string',
            'is_gift' => 'nullable|boolean',
            'gift_message' => 'nullable|string',
            'steadfast_consignment_id' => 'nullable|string|max:255',
            'steadfast_tracking_code' => 'nullable|string|max:255',
        ], [
            'items.required' => 'An order needs at least one item.',
            'items.min' => 'An order needs at least one item.',
        ]);

        $validItemIds = $order->items->pluck('id')->all();
        foreach ($data['items'] as $row) {
            if (!in_array((int) $row['id'], $validItemIds, true)) {
                return response()->json(['message' => 'One of the items does not belong to this order.'], 422);
            }
        }

        $oldOrderData = $order->toArray();
        $oldOrderData['items'] = $order->items->toArray();

        DB::transaction(function () use ($order, $data) {
            // Items: update kept rows, delete removed rows
            $keptIds = [];
            $subtotal = 0;
            foreach ($data['items'] as $row) {
                $item = $order->items->firstWhere('id', (int) $row['id']);
                $price = round((float) $row['price'], 2);
                $qty = (int) $row['quantity'];
                $lineTotal = round($price * $qty, 2);

                $item->update([
                    'price' => $price,
                    'quantity' => $qty,
                    'subtotal' => $lineTotal,
                ]);

                $keptIds[] = $item->id;
                $subtotal += $lineTotal;
            }
            $order->items()->whereNotIn('id', $keptIds)->delete();

            // Amounts
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $couponDiscount = round((float) ($data['coupon_discount'] ?? 0), 2);
            $shippingCost = round((float) ($data['shipping_cost'] ?? 0), 2);
            $total = max(0, $subtotal + $shippingCost - $discount - $couponDiscount);

            // Status bookkeeping
            $oldStatus = $order->status;
            $history = is_array($order->update_history) ? $order->update_history : [];
            $timestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];

            if ($oldStatus !== $data['status']) {
                $timestamps[$data['status']] = now()->toDateTimeString();
                $history[] = [
                    'action' => 'status_updated',
                    'old_status' => $oldStatus,
                    'new_status' => $data['status'],
                    'updated_by' => auth()->id() ?? 0,
                    'timestamp' => now()->toDateTimeString(),
                ];
            }

            $history[] = [
                'action' => 'order_edited',
                'updated_by' => auth()->id() ?? 0,
                'timestamp' => now()->toDateTimeString(),
            ];

            $existingShipping = is_array($order->shipping_address) ? $order->shipping_address : [];

            $order->fill([
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'payment_method' => $data['payment_method'] ?? null,
                'order_source' => $data['order_source'],
                'shipping_address' => array_merge($existingShipping, [
                    'name' => $data['shipping']['name'],
                    'phone' => $data['shipping']['phone'],
                    'address' => $data['shipping']['address'],
                    'city' => $data['shipping']['city'] ?? null,
                    'zone' => $data['shipping']['zone'] ?? null,
                ]),
                'discount' => $discount,
                'coupon_discount' => $couponDiscount,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'order_notes' => $data['order_notes'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? null,
                'is_gift' => (bool) ($data['is_gift'] ?? false),
                'gift_message' => $data['gift_message'] ?? null,
                'steadfast_consignment_id' => $data['steadfast_consignment_id'] ?? null,
                'steadfast_tracking_code' => $data['steadfast_tracking_code'] ?? null,
                'status_timestamps' => $timestamps,
                'update_history' => $history,
            ]);
            $order->save();
        });

        // Capture after state and log
        $newOrderData = $order->fresh()->toArray();
        $newOrderData['items'] = $order->fresh()->items->toArray();
        
        \App\Models\Order\OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'order_updated',
            'old_data' => $oldOrderData,
            'new_data' => $newOrderData,
        ]);

        return response()->json([
            'message' => 'Order updated successfully',
            'redirect' => route('admin.orders.show', $order->order_id),
        ]);
    }
}
