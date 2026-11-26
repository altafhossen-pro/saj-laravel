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
            'is_draft' => 'boolean',
            'send_to_meta' => 'nullable|boolean',
        ]);

        $is_draft = $request->boolean('is_draft');
        $initialStatus = $is_draft ? 'draft' : 'confirmed';
        
        $shipping = [
            'name' => $request->customer_name,
            'phone' => $request->customer_contact,
            'address' => $request->shipping_address,
        ];

        $rawSource = strtolower(trim((string)($request->source ?? 'manual')));
        $allowedSources = ['website', 'facebook', 'whatsapp', 'phone', 'email', 'walk-in', 'instagram', 'manual', 'other'];
        $orderSource = in_array($rawSource, $allowedSources) ? $rawSource : 'manual';

        $order = Order::create([
            'user_id' => $request->user_id ?: null,
            'total' => $request->total ?? 0,
            'subtotal' => $request->subtotal ?? 0,
            'shipping_cost' => $request->delivery_charge ?? 0,
            'discount' => $request->discount ?? 0,
            'status' => $initialStatus,
            'payment_method' => 'cod', 
            'payment_status' => 'pending',
            'shipping_address' => $shipping,
            'order_notes' => $request->notes,
            'order_source' => $orderSource,
            'ip_address' => $request->ip(),
            'status_timestamps' => [$initialStatus => now()->toDateTimeString()],
            'guest_info' => $request->visitor_token ? ['visitor_token' => $request->visitor_token] : null,
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

        // Deduct stock and dispatch Meta CAPI Purchase for confirmed manual orders
        if ($initialStatus === 'confirmed') {
            \App\Services\OrderStockService::deductOrderStock($order);

            if ($request->boolean('send_to_meta', true)) {
                try {
                    \App\Services\FacebookCapiService::trackPurchase($order);
                } catch (\Throwable $fbEx) {
                    \Illuminate\Support\Facades\Log::warning("Meta CAPI Purchase tracking error on manual order: " . $fbEx->getMessage());
                }
            }
        }

        \App\Models\Order\OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'Order created via POS (' . ucfirst($initialStatus) . ')',
            'old_values' => null,
            'new_values' => ['status' => $initialStatus]
        ]);

        session()->flash('success', $is_draft ? 'Draft saved successfully!' : 'Order created and confirmed successfully!');

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
            $search = trim($request->search);
            $cleanPhone = preg_replace('/[^0-9]/', '', $search);

            $query->where(function($q) use ($search, $cleanPhone) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('steadfast_consignment_id', 'like', "%{$search}%")
                  ->orWhere('steadfast_tracking_code', 'like', "%{$search}%")
                  ->orWhere('shipping_address->name', 'like', "%{$search}%")
                  ->orWhere('shipping_address->phone', 'like', "%{$search}%")
                  ->orWhere('guest_info->name', 'like', "%{$search}%")
                  ->orWhere('guest_info->phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQ) use ($search, $cleanPhone) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                      if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                          $userQ->orWhere('phone', 'like', "%{$cleanPhone}%");
                      }
                  });

                if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                    $q->orWhere('shipping_address->phone', 'like', "%{$cleanPhone}%")
                      ->orWhere('guest_info->phone', 'like', "%{$cleanPhone}%")
                      ->orWhere('steadfast_consignment_id', 'like', "%{$cleanPhone}%");
                }
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
                'steadfast_consignment_id' => $order->steadfast_consignment_id,
                'steadfast_tracking_code' => $order->steadfast_tracking_code,
                'is_added_into_steadfast' => (bool) $order->is_added_into_steadfast,
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
            'restock_items' => 'nullable|array',
            'restock_items.*.item_id' => 'required_with:restock_items|integer',
            'restock_items.*.quantity' => 'required_with:restock_items|integer|min:0',
            'restock_reason' => 'nullable|string|max:500',
        ]);

        $order = Order::where('order_id', $order_id)->orWhere('id', $order_id)->firstOrFail();
        
        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Granular permission check: cancel_orders vs manage_order_status
        if ($newStatus === 'cancelled') {
            if (!auth()->user()->hasRole('super_admin') && !auth()->user()->can('cancel_orders') && !auth()->user()->can('manage_orders')) {
                return response()->json([
                    'message' => 'Access Denied: You do not have permission to cancel orders.',
                ], 403);
            }
        } else {
            if (!auth()->user()->hasRole('super_admin') && !auth()->user()->can('manage_order_status') && !auth()->user()->can('manage_orders')) {
                return response()->json([
                    'message' => 'Access Denied: You do not have permission to update order status.',
                ], 403);
            }
        }

        // Rule: Shipped orders cannot be cancelled via normal status modal (valid options: delivered or returned)
        if ($oldStatus === 'shipped' && $newStatus === 'cancelled') {
            return response()->json([
                'message' => 'Orders cannot be cancelled once shipped. Please choose "returned" or "delivered".',
            ], 422);
        }

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

        $restockResult = null;
        $statusChanged = ($oldStatus !== $newStatus);
        $hasRestockSelections = !empty($request->input('restock_items'));

        if ($statusChanged || $hasRestockSelections) {
            if ($statusChanged) {
                $order->status = $newStatus;
            }

            // Deduct stock if order is moving from pending to confirmed (or beyond) and stock wasn't deducted yet
            if ($statusChanged && in_array($newStatus, ['confirmed', 'processing', 'shipped', 'delivered']) && !$order->is_stock_deducted) {
                \App\Services\OrderStockService::deductOrderStock($order);
            }

            // Restock items if order is being cancelled or returned and stock was previously deducted
            if (in_array($newStatus, ['cancelled', 'returned']) && $order->is_stock_deducted) {
                $restockSelections = $request->input('restock_items', []);
                if (!empty($restockSelections) && is_array($restockSelections)) {
                    $restockResult = \App\Services\OrderStockService::restockOrderItems(
                        $order,
                        $restockSelections,
                        $newStatus,
                        $request->input('restock_reason')
                    );
                }
            }

            // Reset collected & receivable amounts if returned
            if ($newStatus === 'returned') {
                if ($order->payment_method === 'cod') {
                    $order->payment_status = 'unpaid';
                }
                $order->steadfast_collected_amount = 0.00;
                $order->steadfast_receivable_amount = 0.00;
            }
            
            // Add to status timestamps
            if ($statusChanged) {
                $timestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];
                $timestamps[$newStatus] = now()->toDateTimeString();
                $order->status_timestamps = $timestamps;
            }
            
            // Add to update history
            $history = is_array($order->update_history) ? $order->update_history : [];
            $history[] = [
                'action' => $statusChanged ? 'status_updated' : 'restock_items_updated',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'steadfast_booked' => $steadfastBooked,
                'restock_items_count' => $restockResult['restocked_count'] ?? 0,
                'updated_by' => auth()->id() ?? 0,
                'timestamp' => now()->toDateTimeString()
            ];
            $order->update_history = $history;

            $order->save();

            $sendToMeta = $request->boolean('send_to_meta', true);

            // Trigger Meta Conversions API (CAPI) Purchase upon first confirmation (filtered, authentic orders only)
            if ($statusChanged && $sendToMeta && in_array($newStatus, ['confirmed', 'processing', 'shipped', 'delivered'])) {
                $statusTimestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];
                if (empty($statusTimestamps['fb_purchase_sent_at'])) {
                    try {
                        \App\Services\FacebookCapiService::trackPurchase($order);
                    } catch (\Throwable $fbEx) {
                        \Illuminate\Support\Facades\Log::warning("Meta CAPI Purchase tracking error on confirmation: " . $fbEx->getMessage());
                    }
                }
            }

            // Trigger Delivered Email if status changed to 'delivered'
            if ($statusChanged && $newStatus === 'delivered' && \App\Services\MailConfigService::isMailEnabled() && \App\Models\Setting::get('email_tpl_order_delivered_active', 1)) {
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

        $successMessage = 'Status updated successfully';
        if ($steadfastBooked) {
            $successMessage = 'Status updated to ' . ucfirst($newStatus) . ' and parcel booked in Steadfast successfully!';
        } elseif ($restockResult && !empty($restockResult['message'])) {
            $successMessage = $restockResult['message'];
        } elseif ($statusChanged) {
            $successMessage = 'Order status updated to ' . ucfirst($newStatus) . ' successfully!';
        }

        return response()->json([
            'message' => $successMessage,
            'status' => $order->status,
            'is_stock_deducted' => (bool) $order->is_stock_deducted,
            'reload' => in_array($newStatus, ['returned', 'cancelled']) || $hasRestockSelections,
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

            // Trigger Meta Conversions API (CAPI) Purchase if not yet sent
            if (empty($timestamps['fb_purchase_sent_at'])) {
                try {
                    \App\Services\FacebookCapiService::trackPurchase($order);
                } catch (\Throwable $fbEx) {
                    \Illuminate\Support\Facades\Log::warning("Meta CAPI Purchase tracking error on Steadfast ship: " . $fbEx->getMessage());
                }
            }
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

    public function syncSteadfastTracking($order_id, SteadfastService $steadfastService, \App\Services\TelegramService $telegramService)
    {
        $order = Order::where('order_id', $order_id)->firstOrFail();
        if (!$order->steadfast_consignment_id && !$order->steadfast_tracking_code) {
            return response()->json([
                'success' => false,
                'message' => 'No Steadfast booking exists for this order.'
            ], 404);
        }

        $nowBdt = now()->setTimezone('Asia/Dhaka');
        $trackingHistory = is_array($order->tracking) ? $order->tracking : [];

        // 1. Fetch detailed trackings milestone trail if available
        $apiTrackingsRes = $steadfastService->getTrackingsByInvoice($order->order_id);
        $fetchedTrackings = $apiTrackingsRes['data']['tracking'] 
            ?? ($apiTrackingsRes['data']['trackings'] 
            ?? ($apiTrackingsRes['tracking'] 
            ?? ($apiTrackingsRes['trackings'] ?? [])));

        // 2. Fetch current status & delivery charge
        $statusRes = $order->steadfast_consignment_id 
            ? $steadfastService->getDeliveryStatusByConsignmentId($order->steadfast_consignment_id)
            : $steadfastService->getDeliveryStatusByTrackingCode($order->steadfast_tracking_code);

        $statusData = $statusRes['data'] ?? $statusRes;
        $deliveryStatus = $statusData['delivery_status'] ?? ($statusData['status'] ?? null);
        $apiDeliveryCharge = $statusData['delivery_charge'] ?? null;
        $apiCodAmount = $statusData['cod_amount'] ?? null;

        // Heal existing legacy tracking items missing note/status
        foreach ($trackingHistory as &$existing) {
            if (($existing['courier'] ?? '') === 'Steadfast' && empty($existing['note'])) {
                $existing['note'] = 'Consignment created by Sender(API).';
                $existing['status'] = $existing['status'] ?? 'in_review';
                if (empty($existing['formatted_date']) && !empty($existing['created_at'])) {
                    try {
                        $cDate = \Carbon\Carbon::parse($existing['created_at'])->setTimezone('Asia/Dhaka');
                        $existing['formatted_date'] = $cDate->format('d M Y');
                        $existing['formatted_time'] = $cDate->format('h:i a');
                        $existing['timezone'] = 'BDT';
                    } catch (\Throwable $e) {}
                }
            }
        }
        unset($existing);

        // If Steadfast API returned tracking milestones, merge them
        if (!empty($fetchedTrackings) && is_array($fetchedTrackings)) {
            foreach ($fetchedTrackings as $step) {
                $stepTime = $step['updated_at'] ?? $step['created_at'] ?? null;
                $stepBdt = $stepTime ? \Carbon\Carbon::parse($stepTime)->setTimezone('Asia/Dhaka') : $nowBdt;
                $stepNote = $step['text'] ?? ($step['note'] ?? ($step['message'] ?? null));
                $rawStepStatus = $step['delivery_status'] ?? ($step['status_name'] ?? ($step['status'] ?? null));
                $stepStatus = (is_string($rawStepStatus) && !is_numeric($rawStepStatus)) ? $rawStepStatus : ($deliveryStatus ?: 'in_review');

                if (empty($stepNote)) {
                    $stepNote = "Consignment status: " . ucfirst(str_replace(['_', '-'], ' ', $stepStatus));
                }

                // Check duplicate by note and timestamp
                $alreadyExists = false;
                foreach ($trackingHistory as $existing) {
                    if (($existing['note'] ?? '') === $stepNote && ($existing['timestamp'] ?? '') === $stepBdt->toDateTimeString()) {
                        $alreadyExists = true;
                        break;
                    }
                }

                if (!$alreadyExists) {
                    $trackingHistory[] = [
                        'id' => uniqid('trk_'),
                        'courier' => 'Steadfast',
                        'source' => 'api_sync',
                        'status' => $stepStatus,
                        'note' => $stepNote,
                        'dispatched_by' => $step['dispatched_by'] ?? null,
                        'timestamp' => $stepBdt->toDateTimeString(),
                        'formatted_date' => $stepBdt->format('d M Y'),
                        'formatted_time' => $stepBdt->format('h:i a'),
                        'timezone' => 'BDT',
                        'created_at' => $nowBdt->toDateTimeString(),
                    ];
                }
            }
        }

        // Check if status changed to delivered
        $oldStatus = $order->status;
        $normalizedStatus = strtolower(trim((string)$deliveryStatus));
        $isDelivered = in_array($normalizedStatus, ['delivered', 'partial_delivered', 'partial delivered']);

        // Determine delivery charge & COD
        $deliveryCharge = $apiDeliveryCharge ?: ($order->steadfast_delivery_charge > 0 ? $order->steadfast_delivery_charge : ($order->shipping_cost > 0 ? $order->shipping_cost : 70.0));
        $codAmount = $apiCodAmount !== null ? (float)$apiCodAmount : ($order->steadfast_collected_amount > 0 ? (float)$order->steadfast_collected_amount : ($order->payment_status === 'paid' ? 0.0 : (float)$order->total));

        $payout = \App\Http\Controllers\Api\SteadfastWebhookController::calculatePayout((float)$codAmount, (float)$deliveryCharge);

        $order->steadfast_collected_amount = $payout['cod_amount'];
        $order->steadfast_delivery_charge = $payout['delivery_charge'];
        $order->steadfast_cod_charge = $payout['cod_charge'];
        $order->steadfast_receivable_amount = $payout['net_receivable'];

        if ($isDelivered && $oldStatus !== 'delivered') {
            $order->status = 'delivered';
            $order->payment_status = 'paid';
            $timestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];
            $timestamps['delivered'] = now()->toDateTimeString();
            $order->status_timestamps = $timestamps;

            // Send Telegram message to admin (NO SMS or Mail to user as instructed)
            if ($telegramService->isConfigured()) {
                try {
                    $shipping = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address ?? '[]', true);
                    $customerName = $shipping['name'] ?? optional($order->user)->name ?? 'Customer';
                    $customerPhone = $shipping['phone'] ?? optional($order->user)->phone ?? 'N/A';
                    $addressParts = array_filter([$shipping['address'] ?? '', $shipping['city'] ?? '', $shipping['zone'] ?? '']);
                    $customerAddress = implode(', ', $addressParts);

                    $msg = "🎉 <b>Steadfast Parcel Delivered!</b>\n\n";
                    $msg .= "📦 <b>Order ID:</b> #{$order->order_id}\n";
                    $msg .= "👤 <b>Customer:</b> {$customerName} ({$customerPhone})\n";
                    if (!empty($customerAddress)) {
                        $msg .= "📍 <b>Address:</b> {$customerAddress}\n";
                    }
                    $msg .= "🚚 <b>Consignment ID:</b> #" . ($order->steadfast_consignment_id ?: 'N/A') . "\n";
                    if (!empty($order->steadfast_tracking_code)) {
                        $msg .= "🔍 <b>Tracking Code:</b> {$order->steadfast_tracking_code}\n";
                    }
                    $msg .= "\n<b>💰 Financial Breakdown:</b>\n";
                    $msg .= "• Cash Collected: ৳" . number_format($payout['cod_amount'], 2) . "\n";
                    $msg .= "• Delivery Charge: −৳" . number_format($payout['delivery_charge'], 2) . "\n";
                    $msg .= "• COD Charge (1%): −৳" . number_format($payout['cod_charge'], 2) . "\n";
                    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
                    $msg .= "💵 <b>You Receive (Profit): ৳" . number_format($payout['net_receivable'], 2) . "</b>\n\n";
                    $msg .= "⏰ " . $nowBdt->format('d M Y, h:i A') . " BDT";

                    $telegramService->sendMessage($msg, null, 'HTML');
                } catch (\Throwable $tEx) {}
            }
        }

        $order->tracking = $trackingHistory;
        $order->save();

        // Sort latest on top for UI
        usort($trackingHistory, function($a, $b) {
            return strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? '');
        });

        return response()->json([
            'success' => true,
            'message' => 'Steadfast tracking synced successfully.',
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'delivery_status' => $deliveryStatus,
            'tracking' => $trackingHistory,
            'payout' => $payout,
            'order' => [
                'collected_amount' => $order->steadfast_collected_amount,
                'delivery_charge' => $order->steadfast_delivery_charge,
                'cod_charge' => $order->steadfast_cod_charge,
                'receivable_amount' => $order->steadfast_receivable_amount,
            ]
        ]);
    }

    public function sendMetaPurchase(Request $request, $order_id)
    {
        if (!auth()->user()->hasRole('super_admin') && !auth()->user()->can('manage_orders')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $order = Order::where('order_id', $order_id)->orWhere('id', $order_id)->firstOrFail();

        $statusTimestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];
        if (!empty($statusTimestamps['fb_purchase_sent_at'])) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase event already sent to Meta on ' . $statusTimestamps['fb_purchase_sent_at'],
                'already_sent' => true,
                'sent_at' => $statusTimestamps['fb_purchase_sent_at'],
            ], 422);
        }

        try {
            $sent = \App\Services\FacebookCapiService::trackPurchase($order);
            if ($sent) {
                $order->refresh();
                $ts = is_array($order->status_timestamps) ? $order->status_timestamps : [];
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase event sent to Meta successfully! 🎉',
                    'sent_at' => $ts['fb_purchase_sent_at'] ?? now()->toDateTimeString(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Meta CAPI could not send the event. Check Meta credentials/token.',
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending Purchase event: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkSteadfastFraud(Request $request, SteadfastService $steadfastService)
    {
        $phone = $request->query('phone');
        $orderId = $request->query('order_id');
        $force = filter_var($request->query('force', false), FILTER_VALIDATE_BOOLEAN);

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Phone number is required.'], 422);
        }

        $cleanPhone = $steadfastService->formatPhone($phone);
        if (empty($cleanPhone)) {
            return response()->json(['success' => false, 'message' => 'Invalid phone number.'], 422);
        }

        // Determine if this order is > 10 days old
        $isOrderOld = false;
        if ($orderId) {
            $order = Order::where('order_id', $orderId)->orWhere('id', $orderId)->first();
            if ($order && $order->created_at) {
                $isOrderOld = $order->created_at->lt(now()->subDays(10));
            }
        }

        // Check persistent DB cache
        $record = \App\Models\CustomerDeliveryRecord::where('phone', $cleanPhone)->first();
        $isFresh = $record && $record->isFresh();

        // 1. If order is older than 10 days and not force:
        // Policy: Do NOT call Steadfast API automatically to protect rate limits!
        if ($isOrderOld && !$force) {
            if ($record) {
                return response()->json([
                    'success' => true,
                    'source' => 'cache_db_old_order',
                    'is_cached' => true,
                    'is_old_order' => true,
                    'message' => 'Order is older than 10 days (served from persistent database cache).',
                    'data' => $record->toDisplayArray(),
                ]);
            }

            return response()->json([
                'success' => true,
                'source' => 'none',
                'is_cached' => false,
                'is_old_order' => true,
                'message' => 'Order is older than 10 days. Click refresh button to check record manually.',
                'data' => null,
            ]);
        }

        // 2. If cached within 24 hours and not force:
        // Return cached data immediately without touching Steadfast API!
        if ($isFresh && !$force) {
            return response()->json([
                'success' => true,
                'source' => 'cache_db_24h',
                'is_cached' => true,
                'is_old_order' => $isOrderOld,
                'message' => 'Customer record served from persistent 24h cache.',
                'data' => $record->toDisplayArray(),
            ]);
        }

        // 3. Otherwise (cache expired, new phone, or force clicked by admin with captcha):
        // Query Steadfast API
        $apiResult = $steadfastService->checkFraud($cleanPhone);

        if (!empty($apiResult['success']) && isset($apiResult['data'])) {
            $apiData = $apiResult['data'];
            
            $record = \App\Models\CustomerDeliveryRecord::updateOrCreate(
                ['phone' => $cleanPhone],
                [
                    'delivery_ratio' => $apiData['delivery_ratio'] ?? null,
                    'cancellation_ratio' => $apiData['cancellation_ratio'] ?? null,
                    'volume_band' => $apiData['volume_band'] ?? null,
                    'volume_range' => $apiData['volume_range'] ?? null,
                    'total_reports' => (int) ($apiData['total_reports'] ?? 0),
                    'fraud_categories' => $apiData['fraud_categories'] ?? [],
                    'raw_response' => $apiData,
                    'checked_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'source' => 'api_live',
                'is_cached' => false,
                'is_old_order' => $isOrderOld,
                'message' => 'Fresh record retrieved from Steadfast & cached in database for 24h.',
                'data' => $record->toDisplayArray(),
            ]);
        }

        // If Steadfast API returned error or rate limit (429), fallback gracefully to existing DB record if available!
        if ($record) {
            return response()->json([
                'success' => true,
                'source' => 'fallback_db',
                'is_cached' => true,
                'is_old_order' => $isOrderOld,
                'message' => 'Steadfast rate limit or unavailable. Showing last cached record.',
                'data' => $record->toDisplayArray(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $apiResult['message'] ?? 'Failed to retrieve record from Steadfast.',
            'status' => $apiResult['status'] ?? 500,
        ], $apiResult['status'] ?? 500);
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
            'items.*.id' => 'nullable',
            'items.*.product_id' => 'nullable|integer',
            'items.*.variant_sku' => 'nullable|string|max:100',
            'items.*.name' => 'required|string|max:255',
            'items.*.image' => 'nullable|string',
            'items.*.variant_info' => 'nullable|array',
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
            'steadfast_delivery_charge' => 'nullable|numeric|min:0',
            'steadfast_collected_amount' => 'nullable|numeric|min:0',
        ], [
            'items.required' => 'An order needs at least one item.',
            'items.min' => 'An order needs at least one item.',
        ]);

        $validItemIds = $order->items->pluck('id')->all();
        foreach ($data['items'] as $row) {
            $rowId = !empty($row['id']) ? (int) $row['id'] : null;
            if ($rowId && !in_array($rowId, $validItemIds, true)) {
                return response()->json(['message' => 'One of the items does not belong to this order.'], 422);
            }
        }

        $oldOrderData = $order->toArray();
        $oldOrderData['items'] = $order->items->toArray();

        DB::transaction(function () use ($order, $data, $validItemIds) {
            // Items: update kept rows, create newly added rows, delete removed rows
            $keptIds = [];
            $subtotal = 0;
            foreach ($data['items'] as $row) {
                $rowId = !empty($row['id']) ? (int) $row['id'] : null;
                $price = round((float) $row['price'], 2);
                $qty = (int) $row['quantity'];
                $lineTotal = round($price * $qty, 2);

                if ($rowId && in_array($rowId, $validItemIds, true)) {
                    $item = $order->items->firstWhere('id', $rowId);
                    $item->update([
                        'price' => $price,
                        'quantity' => $qty,
                        'subtotal' => $lineTotal,
                    ]);
                    $keptIds[] = $item->id;
                } else {
                    $newItem = $order->items()->create([
                        'product_id' => $row['product_id'] ?? null,
                        'variant_sku' => $row['variant_sku'] ?? null,
                        'name' => $row['name'],
                        'image' => $row['image'] ?? null,
                        'price' => $price,
                        'quantity' => $qty,
                        'subtotal' => $lineTotal,
                        'variant_info' => $row['variant_info'] ?? null,
                    ]);
                    $keptIds[] = $newItem->id;
                }

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

                // Deduct stock if order was pending and is now confirmed or beyond
                if (in_array($data['status'], ['confirmed', 'processing', 'shipped', 'delivered']) && !$order->is_stock_deducted) {
                    \App\Services\OrderStockService::deductOrderStock($order);
                }
            }

            $history[] = [
                'action' => 'order_edited',
                'updated_by' => auth()->id() ?? 0,
                'timestamp' => now()->toDateTimeString(),
            ];

            $existingShipping = is_array($order->shipping_address) ? $order->shipping_address : [];

            $adminNotesData = $data['admin_notes'] ?? null;
            if (is_array($adminNotesData)) {
                $adminNotesData = json_encode($adminNotesData, JSON_UNESCAPED_UNICODE);
            }

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
                'admin_notes' => $adminNotesData,
                'is_gift' => (bool) ($data['is_gift'] ?? false),
                'gift_message' => $data['gift_message'] ?? null,
                'steadfast_consignment_id' => $data['steadfast_consignment_id'] ?? null,
                'steadfast_tracking_code' => $data['steadfast_tracking_code'] ?? null,
                'status_timestamps' => $timestamps,
                'update_history' => $history,
            ]);
            $order->save();

            // Re-calculate financial settlement if order is booked into Steadfast or courier consignment exists
            if ($order->is_added_into_steadfast || !empty($data['steadfast_consignment_id']) || !empty($data['steadfast_tracking_code'])) {
                $courierDeliveryCharge = isset($data['steadfast_delivery_charge']) && $data['steadfast_delivery_charge'] !== ''
                    ? (float) $data['steadfast_delivery_charge']
                    : ($order->steadfast_delivery_charge > 0 ? (float) $order->steadfast_delivery_charge : (float) $shippingCost);

                $collectedCod = isset($data['steadfast_collected_amount']) && $data['steadfast_collected_amount'] !== ''
                    ? (float) $data['steadfast_collected_amount']
                    : ($data['payment_status'] === 'paid' ? 0.0 : (float) $total);

                $payout = \App\Http\Controllers\Api\SteadfastWebhookController::calculatePayout($collectedCod, $courierDeliveryCharge);

                $order->update([
                    'steadfast_collected_amount' => $payout['cod_amount'],
                    'steadfast_delivery_charge' => $payout['delivery_charge'],
                    'steadfast_cod_charge' => $payout['cod_charge'],
                    'steadfast_receivable_amount' => $payout['net_receivable'],
                ]);
            }

            // Trigger Meta Conversions API (CAPI) Purchase upon first confirmation (filtered, authentic orders only)
            if (in_array($data['status'], ['confirmed', 'processing', 'shipped', 'delivered'])) {
                $statusTimestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];
                if (empty($statusTimestamps['fb_purchase_sent_at'])) {
                    try {
                        \App\Services\FacebookCapiService::trackPurchase($order);
                    } catch (\Throwable $fbEx) {
                        \Illuminate\Support\Facades\Log::warning("Meta CAPI Purchase tracking error on edit confirmation: " . $fbEx->getMessage());
                    }
                }
            }
        });

        // Capture after state and log only if there are actual field changes
        $newOrderData = $order->fresh()->toArray();
        $newOrderData['items'] = $order->fresh()->items->toArray();

        $hasChanges = false;
        $fieldsToCheck = ['status', 'payment_status', 'shipping_cost', 'discount', 'coupon_discount', 'total', 'order_notes', 'admin_notes', 'is_gift', 'gift_message'];
        foreach ($fieldsToCheck as $field) {
            if (($oldOrderData[$field] ?? null) != ($newOrderData[$field] ?? null)) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            $oldShip = is_array($oldOrderData['shipping_address'] ?? null) ? $oldOrderData['shipping_address'] : json_decode($oldOrderData['shipping_address'] ?? '[]', true);
            $newShip = is_array($newOrderData['shipping_address'] ?? null) ? $newOrderData['shipping_address'] : json_decode($newOrderData['shipping_address'] ?? '[]', true);
            if ($oldShip != $newShip) {
                $hasChanges = true;
            }
        }

        if (!$hasChanges) {
            $oldItems = collect($oldOrderData['items'] ?? [])->map(fn($i) => ['id' => $i['id'] ?? null, 'qty' => $i['quantity'] ?? null, 'price' => $i['price'] ?? null])->values();
            $newItems = collect($newOrderData['items'] ?? [])->map(fn($i) => ['id' => $i['id'] ?? null, 'qty' => $i['quantity'] ?? null, 'price' => $i['price'] ?? null])->values();
            if ($oldItems->toJson() !== $newItems->toJson()) {
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            \App\Models\Order\OrderLog::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => 'order_updated',
                'old_data' => $oldOrderData,
                'new_data' => $newOrderData,
            ]);
        }

        return response()->json([
            'message' => 'Order updated successfully',
            'redirect' => route('admin.orders.show', $order->order_id),
        ]);
    }

    public function saveAdminNote(Request $request, $order_id)
    {
        $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $order = Order::where('order_id', $order_id)->firstOrFail();
        $nowBdt = now()->setTimezone('Asia/Dhaka');
        $user = auth()->user();

        $rawNotes = $order->admin_notes;
        $notesList = [];

        if (!empty($rawNotes)) {
            $decoded = json_decode($rawNotes, true);
            if (is_array($decoded)) {
                $notesList = $decoded;
            } else {
                $notesList[] = [
                    'id' => uniqid('note_'),
                    'note' => (string) $rawNotes,
                    'admin_name' => 'Admin',
                    'timestamp' => $order->updated_at ? $order->updated_at->setTimezone('Asia/Dhaka')->toDateTimeString() : $nowBdt->toDateTimeString(),
                    'formatted_date' => $order->updated_at ? $order->updated_at->setTimezone('Asia/Dhaka')->format('d M Y') : $nowBdt->format('d M Y'),
                    'formatted_time' => $order->updated_at ? $order->updated_at->setTimezone('Asia/Dhaka')->format('h:i a') : $nowBdt->format('h:i a'),
                    'timezone' => 'BDT',
                ];
            }
        }

        $newEntry = [
            'id' => uniqid('note_'),
            'note' => trim($request->input('note')),
            'admin_name' => $user ? $user->name : 'Admin',
            'admin_id' => $user ? $user->id : null,
            'timestamp' => $nowBdt->toDateTimeString(),
            'formatted_date' => $nowBdt->format('d M Y'),
            'formatted_time' => $nowBdt->format('h:i a'),
            'timezone' => 'BDT',
        ];

        array_unshift($notesList, $newEntry);

        $order->admin_notes = json_encode($notesList, JSON_UNESCAPED_UNICODE);
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Admin note saved successfully!',
            'notes' => $notesList,
        ]);
    }

    public function globalSearch(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([
                'orders' => [],
                'products' => [],
            ]);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $q);

        // 1. Search Orders
        $orderQuery = Order::with('user');
        $orderQuery->where(function ($query) use ($q, $cleanPhone) {
            $query->where('order_id', 'like', "%{$q}%")
                  ->orWhere('steadfast_consignment_id', 'like', "%{$q}%")
                  ->orWhere('steadfast_tracking_code', 'like', "%{$q}%")
                  ->orWhere('shipping_address->phone', 'like', "%{$q}%")
                  ->orWhere('shipping_address->name', 'like', "%{$q}%")
                  ->orWhere('guest_info->phone', 'like', "%{$q}%")
                  ->orWhere('guest_info->name', 'like', "%{$q}%")
                  ->orWhereHas('user', function ($u) use ($q, $cleanPhone) {
                      $u->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                      if (!empty($cleanPhone)) {
                          $u->orWhere('phone', 'like', "%{$cleanPhone}%");
                      }
                  });

            if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                $query->orWhere('shipping_address->phone', 'like', "%{$cleanPhone}%")
                      ->orWhere('guest_info->phone', 'like', "%{$cleanPhone}%")
                      ->orWhere('steadfast_consignment_id', 'like', "%{$cleanPhone}%");
            }
        });

        $matchedOrders = $orderQuery->orderBy('created_at', 'desc')->limit(6)->get()->map(function ($order) {
            $shipping = is_array($order->shipping_address) ? $order->shipping_address : [];
            $guest = is_array($order->guest_info) ? $order->guest_info : [];
            $customerName = $shipping['name'] ?? ($guest['name'] ?? optional($order->user)->name ?? 'Customer');
            $customerPhone = $shipping['phone'] ?? ($guest['phone'] ?? optional($order->user)->phone ?? '');

            return [
                'id' => $order->id,
                'order_id' => $order->order_id,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'steadfast_consignment_id' => $order->steadfast_consignment_id,
                'status' => $order->status,
                'total' => number_format((float) $order->total, 2),
                'date' => $order->created_at ? $order->created_at->format('d M Y') : '',
                'url' => route('admin.orders.show', $order->order_id),
            ];
        });

        // 2. Search Products
        $matchedProducts = \App\Models\Product\Product::select('id', 'title', 'product_type', 'featured_image', 'base_price', 'total_stock')
            ->with(['variants' => function ($vQuery) {
                $vQuery->select('id', 'product_id', 'sku', 'current_price', 'stock_quantity');
            }])
            ->where(function ($pQuery) use ($q) {
                $pQuery->where('title', 'like', "%{$q}%")
                       ->orWhereHas('variants', function ($v) use ($q) {
                           $v->where('sku', 'like', "%{$q}%");
                       });
            })
            ->limit(6)
            ->get()
            ->map(function ($prod) {
                $firstVariant = $prod->variants->first();
                $sku = $firstVariant ? $firstVariant->sku : null;
                $img = $prod->featured_image;
                $imgUrl = null;
                if ($img) {
                    $imgUrl = \Illuminate\Support\Str::startsWith($img, ['http://', 'https://']) ? $img : \Illuminate\Support\Facades\Storage::url($img);
                }

                return [
                    'id' => $prod->id,
                    'title' => $prod->title,
                    'sku' => $sku,
                    'image' => $imgUrl,
                    'price' => number_format((float) $prod->base_price, 2),
                    'stock' => $prod->total_stock ?? 0,
                    'type' => $prod->product_type,
                    'url' => route('admin.products.edit', $prod->id),
                ];
            });

        return response()->json([
            'orders' => $matchedOrders,
            'products' => $matchedProducts,
        ]);
    }

    /**
     * Soft delete an order.
     */
    public function destroy(Request $request, $order_id)
    {
        $order = Order::where('order_id', $order_id)
            ->orWhere('id', $order_id)
            ->firstOrFail();

        $orderIdDisplay = $order->order_id ?: ('#' . $order->id);

        // Record log entry in order_logs before soft delete
        \App\Models\Order\OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => "Order {$orderIdDisplay} moved to trash (Soft Deleted)",
            'old_data' => [
                'status' => $order->status,
                'total' => (float) $order->total,
                'deleted_by' => auth()->user()?->name ?? 'Admin',
            ],
            'new_data' => [
                'deleted_at' => now()->toDateTimeString(),
            ],
        ]);

        // Soft delete the order
        $order->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Order {$orderIdDisplay} has been moved to trash successfully.",
            ]);
        }

        return redirect()->route('admin.orders')
            ->with('success', "Order {$orderIdDisplay} has been moved to trash successfully.");
    }

    /**
     * Show Order Trash page (Super Admin Only).
     */
    public function trash(Request $request)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized: Only Super Admin can access the Order Trash.');
        }

        $trashedCount = Order::onlyTrashed()->count();

        return view('admin.orders.trash', compact('trashedCount'));
    }

    /**
     * Fetch trashed orders list with filters & pagination (Super Admin Only).
     */
    public function fetchTrashOrders(Request $request)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = Order::onlyTrashed()->with(['items', 'user']);

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $cleanPhone = preg_replace('/[^0-9]/', '', $search);

            $query->where(function($q) use ($search, $cleanPhone) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('steadfast_consignment_id', 'like', "%{$search}%")
                  ->orWhere('steadfast_tracking_code', 'like', "%{$search}%")
                  ->orWhere('shipping_address->name', 'like', "%{$search}%")
                  ->orWhere('shipping_address->phone', 'like', "%{$search}%")
                  ->orWhere('guest_info->name', 'like', "%{$search}%")
                  ->orWhere('guest_info->phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQ) use ($search, $cleanPhone) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                      if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                          $userQ->orWhere('phone', 'like', "%{$cleanPhone}%");
                      }
                  });

                if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                    $q->orWhere('shipping_address->phone', 'like', "%{$cleanPhone}%")
                      ->orWhere('guest_info->phone', 'like', "%{$cleanPhone}%")
                      ->orWhere('steadfast_consignment_id', 'like', "%{$cleanPhone}%");
                }
            });
        }

        // Status filter (original order status before trash)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Date Filter (based on deleted_at)
        if ($request->filled('date_from')) {
            $query->whereDate('deleted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('deleted_at', '<=', $request->date_to);
        }

        // Sorting
        $sortField = $request->input('sort_field', 'deleted_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['deleted_at', 'created_at', 'total', 'order_id'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('deleted_at', 'desc');
        }

        $perPage = max(5, min(200, (int) $request->input('per_page', 10)));
        $orders = $query->paginate($perPage);

        // Fetch logs for these orders to find who moved them to trash if available
        $orderIds = $orders->pluck('id')->toArray();
        $trashLogs = \App\Models\Order\OrderLog::whereIn('order_id', $orderIds)
            ->where('action', 'like', '%moved to trash%')
            ->with('user:id,name')
            ->latest()
            ->get()
            ->keyBy('order_id');

        $mappedOrders = $orders->map(function($order) use ($trashLogs) {
            $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address ?? '[]', true);
            $customerName = $shippingAddress['name'] ?? ($order->user->name ?? 'Guest');
            $customerPhone = $shippingAddress['phone'] ?? ($order->user->phone ?? '');
            
            $log = $trashLogs->get($order->id);
            $deletedBy = $log && $log->user ? $log->user->name : ($log['old_data']['deleted_by'] ?? 'Admin');

            $deletedAtCarbon = $order->deleted_at ? \Carbon\Carbon::parse($order->deleted_at)->setTimezone('Asia/Dhaka') : null;

            return [
                'id' => $order->id,
                'order_id' => $order->order_id ?: ('#' . $order->id),
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_type' => $order->user_id ? 'Registered' : 'Guest',
                'user_id' => $order->user_id,
                'created_at' => $order->created_at ? $order->created_at->format('M d, Y h:i A') : '',
                'deleted_at' => $deletedAtCarbon ? $deletedAtCarbon->format('M d, Y h:i A') : '',
                'deleted_ago' => $deletedAtCarbon ? $deletedAtCarbon->diffForHumans() : '',
                'deleted_by' => $deletedBy,
                'status' => $order->status,
                'payment_status' => $order->payment_status ?? 'pending',
                'source' => $order->order_source ?? 'website',
                'items_count' => $order->items ? $order->items->count() : 0,
                'total_quantity' => $order->items ? $order->items->sum('quantity') : 0,
                'total' => number_format((float) $order->total, 2),
                'raw_total' => (float) $order->total,
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
            ],
            'total_trashed' => $orders->total(),
        ]);
    }

    /**
     * Generate Math CAPTCHA for permanent order deletion (Super Admin Only).
     */
    public function getTrashCaptcha(Request $request)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $n1 = rand(1, 9);
        $n2 = rand(1, 9);
        $answer = $n1 + $n2;

        $tokenPayload = [
            'answer' => $answer,
            'time' => time(),
            'user_id' => auth()->id(),
        ];

        $token = \Illuminate\Support\Facades\Crypt::encryptString(json_encode($tokenPayload));

        return response()->json([
            'success' => true,
            'question' => "{$n1} + {$n2} = ?",
            'token' => $token,
        ]);
    }

    /**
     * Restore a trashed order back to active (Super Admin Only).
     */
    public function restore(Request $request, $order_id)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $order = Order::onlyTrashed()
            ->where(function($q) use ($order_id) {
                $q->where('order_id', $order_id)
                  ->orWhere('id', $order_id);
            })
            ->firstOrFail();

        $orderIdDisplay = $order->order_id ?: ('#' . $order->id);

        $order->restore();

        // Log restore event
        \App\Models\Order\OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => "Order {$orderIdDisplay} restored from trash",
            'old_data' => [
                'deleted_at' => now()->toDateTimeString(),
            ],
            'new_data' => [
                'restored_by' => auth()->user()?->name ?? 'Super Admin',
                'restored_at' => now()->toDateTimeString(),
                'status' => $order->status,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Order {$orderIdDisplay} has been successfully restored to active orders.",
        ]);
    }

    /**
     * Permanently delete an order with Math CAPTCHA validation (Super Admin Only).
     */
    public function forceDelete(Request $request, $order_id)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'captcha_answer' => 'required',
            'captcha_token' => 'required|string',
        ]);

        // Decrypt and validate CAPTCHA
        try {
            $payload = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($request->captcha_token), true);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired CAPTCHA token. Please solve a new math challenge.',
            ], 422);
        }

        if (!$payload || !isset($payload['answer']) || !isset($payload['time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid CAPTCHA challenge.',
            ], 422);
        }

        // Check expiry (valid for 5 minutes)
        if (time() - $payload['time'] > 300) {
            return response()->json([
                'success' => false,
                'message' => 'CAPTCHA expired. Please try with a new challenge.',
            ], 422);
        }

        // Validate answer
        if (intval(trim($request->captcha_answer)) !== intval($payload['answer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect CAPTCHA answer. Verification failed.',
            ], 422);
        }

        $order = Order::onlyTrashed()
            ->where(function($q) use ($order_id) {
                $q->where('order_id', $order_id)
                  ->orWhere('id', $order_id);
            })
            ->firstOrFail();

        $orderIdDisplay = $order->order_id ?: ('#' . $order->id);

        // Delete order items
        \App\Models\Order\OrderItem::where('order_id', $order->id)->delete();

        // Delete order logs
        \App\Models\Order\OrderLog::where('order_id', $order->id)->delete();

        // Permanently force delete the order record
        $order->forceDelete();

        return response()->json([
            'success' => true,
            'message' => "Order {$orderIdDisplay} has been permanently deleted from database.",
        ]);
    }

    /**
     * Bulk restore multiple trashed orders (Super Admin Only).
     */
    public function bulkRestore(Request $request)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required',
        ]);

        $ids = $request->order_ids;

        $orders = Order::onlyTrashed()
            ->where(function($q) use ($ids) {
                $q->whereIn('id', $ids)
                  ->orWhereIn('order_id', $ids);
            })
            ->get();

        $count = $orders->count();
        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'No matching trashed orders found.'], 404);
        }

        foreach ($orders as $order) {
            $order->restore();

            \App\Models\Order\OrderLog::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => "Order #{$order->order_id} bulk restored from trash",
                'old_data' => ['deleted_at' => now()->toDateTimeString()],
                'new_data' => [
                    'restored_by' => auth()->user()?->name ?? 'Super Admin',
                    'restored_at' => now()->toDateTimeString(),
                    'bulk' => true,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "Successfully restored {$count} order" . ($count > 1 ? 's' : '') . " back to active orders.",
        ]);
    }

    /**
     * Bulk permanently delete multiple trashed orders with Math CAPTCHA (Super Admin Only).
     */
    public function bulkForceDelete(Request $request)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required',
            'captcha_answer' => 'required',
            'captcha_token' => 'required|string',
        ]);

        // Decrypt and validate CAPTCHA
        try {
            $payload = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($request->captcha_token), true);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired CAPTCHA token. Please solve a new math challenge.',
            ], 422);
        }

        if (!$payload || !isset($payload['answer']) || !isset($payload['time'])) {
            return response()->json(['success' => false, 'message' => 'Invalid CAPTCHA challenge.'], 422);
        }

        if (time() - $payload['time'] > 300) {
            return response()->json(['success' => false, 'message' => 'CAPTCHA expired. Please try with a new challenge.'], 422);
        }

        if (intval(trim($request->captcha_answer)) !== intval($payload['answer'])) {
            return response()->json(['success' => false, 'message' => 'Incorrect CAPTCHA answer. Verification failed.'], 422);
        }

        $ids = $request->order_ids;

        $orders = Order::onlyTrashed()
            ->where(function($q) use ($ids) {
                $q->whereIn('id', $ids)
                  ->orWhereIn('order_id', $ids);
            })
            ->get();

        $count = $orders->count();
        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'No matching trashed orders found.'], 404);
        }

        $internalIds = $orders->pluck('id')->toArray();

        // Delete items and logs
        \App\Models\Order\OrderItem::whereIn('order_id', $internalIds)->delete();
        \App\Models\Order\OrderLog::whereIn('order_id', $internalIds)->delete();

        // Force delete orders
        Order::onlyTrashed()->whereIn('id', $internalIds)->forceDelete();

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "Successfully permanently deleted {$count} order" . ($count > 1 ? 's' : '') . " from database.",
        ]);
    }
}
