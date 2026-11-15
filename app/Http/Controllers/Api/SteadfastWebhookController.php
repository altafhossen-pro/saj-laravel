<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderLog;
use App\Models\Setting;
use App\Services\SteadfastLogger;
use App\Services\SteadfastService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SteadfastWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from Steadfast Courier safely without crashing.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $headers = [
                'x-signature' => $request->header('X-Signature'),
                'x-webhook-token' => $request->header('X-Webhook-Token'),
                'authorization' => $request->header('Authorization'),
                'content-type' => $request->header('Content-Type'),
                'user-agent' => $request->header('User-Agent'),
                'ip' => $request->ip(),
            ];
            $payload = $request->all();

            // Note: Success and routine info are not logged to steadfast.log as per requirements (errors only).
            Log::info('Steadfast Webhook Received', ['headers' => $headers, 'payload' => $payload]);

            // 1. Signature / Auth Token Verification
            $configuredToken = Setting::get('steadfast_webhook_token');
            if (!empty($configuredToken)) {
                $providedSignature = $request->header('X-Signature')
                    ?? $request->header('X-Webhook-Token')
                    ?? $request->bearerToken()
                    ?? $request->input('token');

                $rawContent = $request->getContent();
                $expectedHmac = hash_hmac('sha256', $rawContent, $configuredToken);

                $isValid = false;
                if (!empty($providedSignature)) {
                    if (hash_equals($expectedHmac, $providedSignature)) {
                        $isValid = true;
                    } elseif (hash_equals($configuredToken, $providedSignature)) {
                        $isValid = true;
                    }
                }

                if (!$isValid) {
                    SteadfastLogger::warning('Webhook Unauthorized: Token or Signature mismatch', [
                        'provided' => $providedSignature,
                        'expected_hmac' => $expectedHmac,
                        'ip' => $request->ip(),
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized: Invalid signature or token.',
                    ], 401);
                }
            }

            // 2. Extract Identifier & Payload Data
            $consignmentId = $request->input('consignment_id') ?? $request->input('consignmentId');
            $invoice = $request->input('invoice') ?? $request->input('order_id') ?? $request->input('invoice_id');
            $trackingCode = $request->input('tracking_code') ?? $request->input('trackingCode');
            $rawStatus = (string) ($request->input('status') ?? $request->input('delivery_status') ?? $request->input('event') ?? '');
            $codAmountInput = $request->input('cod_amount') ?? $request->input('collected_amount');
            $deliveryChargeInput = $request->input('delivery_charge') ?? $request->input('delivery_fee') ?? $request->input('delivery_cost');
            $note = $request->input('note') 
                ?? $request->input('text') 
                ?? $request->input('tracking_message') 
                ?? $request->input('comment') 
                ?? $request->input('comments') 
                ?? $request->input('rider_note') 
                ?? $request->input('message');
            $rawTimestamp = $request->input('updated_at') ?? $request->input('created_at') ?? $request->input('timestamp');

            if (empty($consignmentId) && empty($invoice) && empty($trackingCode)) {
                SteadfastLogger::warning('Webhook rejected: Missing order identifier (consignment_id, invoice, or tracking_code)', [
                    'payload' => $payload,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing order identifier (consignment_id, invoice, or tracking_code).',
                ], 422);
            }

            // 3. Find Order in Database
            $order = null;
            if (!empty($consignmentId)) {
                $order = Order::where('steadfast_consignment_id', (string) $consignmentId)->first();
            }
            if (!$order && !empty($invoice)) {
                $order = Order::where('order_id', (string) $invoice)->first();
            }
            if (!$order && !empty($trackingCode)) {
                $order = Order::where('steadfast_tracking_code', (string) $trackingCode)->first();
            }

            if (!$order) {
                SteadfastLogger::warning('Webhook: Order not found in database for provided identifiers', [
                    'consignment_id' => $consignmentId,
                    'invoice' => $invoice,
                    'tracking_code' => $trackingCode,
                    'raw_status' => $rawStatus,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found for given consignment_id or invoice.',
                ], 404);
            }

            $oldStatus = $order->status;
            $oldPaymentStatus = $order->payment_status;
            $normalizedStatus = strtolower(trim($rawStatus));

            // 4. Map Courier Status to Order Status
            $newOrderStatus = null;
            $newPaymentStatus = null;
            $isDelivered = false;

            switch ($normalizedStatus) {
                case 'delivered':
                case 'partial_delivered':
                case 'partial delivered':
                    $newOrderStatus = 'delivered';
                    $newPaymentStatus = 'paid';
                    $isDelivered = true;
                    break;

                case 'cancelled':
                case 'cancelled_by_customer':
                case 'cancelled_by_merchant':
                    $newOrderStatus = 'cancelled';
                    break;

                case 'return':
                case 'returned':
                case 'return_received':
                case 'return_pending':
                    $newOrderStatus = 'returned';
                    break;

                case 'in_transit':
                case 'transit':
                case 'picked':
                case 'picked_up':
                case 'out_for_delivery':
                case 'delivery_in_progress':
                    $newOrderStatus = 'shipped';
                    break;

                case 'pending':
                case 'in_review':
                case 'delivery_approval_pending':
                case 'delivered_approval_pending':
                case 'partial_delivered_approval_pending':
                case 'cancelled_approval_pending':
                case 'hold':
                default:
                    // No core status transition required, recorded in tracking log
                    $newOrderStatus = null;
                    break;
            }

            // 5. Determine Delivery Charge and COD Amount
            $deliveryCharge = $deliveryChargeInput;
            if ($deliveryCharge === null || $deliveryCharge === '') {
                if ($order->steadfast_delivery_charge > 0) {
                    $deliveryCharge = (float) $order->steadfast_delivery_charge;
                } elseif (!empty($consignmentId)) {
                    try {
                        $steadfastService = app(SteadfastService::class);
                        $statusRes = $steadfastService->getDeliveryStatusByConsignmentId($consignmentId);
                        if (!empty($statusRes['data']['delivery_charge'])) {
                            $deliveryCharge = (float) $statusRes['data']['delivery_charge'];
                        } elseif (!empty($statusRes['delivery_charge'])) {
                            $deliveryCharge = (float) $statusRes['delivery_charge'];
                        }
                    } catch (Throwable $ex) {
                        SteadfastLogger::warning('Error querying delivery charge from Steadfast API: ' . $ex->getMessage());
                    }
                }
            }

            if ($deliveryCharge === null || $deliveryCharge === '') {
                $deliveryCharge = (float) ($order->shipping_cost > 0 ? $order->shipping_cost : 70.0);
            } else {
                $deliveryCharge = (float) $deliveryCharge;
            }

            // Determine COD Amount
            if ($codAmountInput !== null && is_numeric($codAmountInput)) {
                $codAmount = (float) $codAmountInput;
            } elseif ($order->payment_status === 'paid' && !$isDelivered) {
                $codAmount = 0.0;
            } else {
                $codAmount = $order->steadfast_collected_amount > 0 ? (float) $order->steadfast_collected_amount : (float) $order->total;
            }

            // 6. Calculate Financial Payout (Profit/Receive)
            $payout = self::calculatePayout($codAmount, $deliveryCharge);

            // 7. Update Order Fields
            $order->is_added_into_steadfast = true;
            if (empty($order->steadfast_consignment_id) && !empty($consignmentId)) {
                $order->steadfast_consignment_id = (string) $consignmentId;
            }
            if (empty($order->steadfast_tracking_code) && !empty($trackingCode)) {
                $order->steadfast_tracking_code = (string) $trackingCode;
            }

            $order->steadfast_collected_amount = $payout['cod_amount'];
            $order->steadfast_delivery_charge = $payout['delivery_charge'];
            $order->steadfast_cod_charge = $payout['cod_charge'];
            $order->steadfast_receivable_amount = $payout['net_receivable'];

            // Update Timestamps & Status
            $timestamps = is_array($order->status_timestamps) ? $order->status_timestamps : [];
            if ($newOrderStatus && $newOrderStatus !== $oldStatus) {
                $order->status = $newOrderStatus;
                $timestamps[$newOrderStatus] = now()->toDateTimeString();
            }
            if ($newPaymentStatus && $order->payment_status !== 'paid') {
                $order->payment_status = $newPaymentStatus;
            }
            $order->status_timestamps = $timestamps;

            // 8. Process Timestamp in Bangladesh Time (Asia/Dhaka)
            $nowBdt = now()->setTimezone('Asia/Dhaka');
            if (!empty($rawTimestamp)) {
                try {
                    $eventBdt = Carbon::parse($rawTimestamp)->setTimezone('Asia/Dhaka');
                } catch (Throwable $e) {
                    $eventBdt = $nowBdt;
                }
            } else {
                $eventBdt = $nowBdt;
            }

            $displayNote = $note;
            if (empty($displayNote)) {
                if (!empty($rawStatus) && !in_array(strtolower($rawStatus), ['update', 'consignment_update', 'tracking_update'])) {
                    $statusName = ucfirst(str_replace(['_', '-'], ' ', $rawStatus));
                    $displayNote = "Parcel status updated to {$statusName}";
                } else {
                    $displayNote = "Parcel details updated via Steadfast";
                }
            }

            // 9. Update Tracking History Array
            $trackingHistory = is_array($order->tracking) ? $order->tracking : [];
            $logEntry = [
                'id' => uniqid('trk_'),
                'courier' => 'Steadfast',
                'source' => 'webhook',
                'status' => (!empty($rawStatus) && !in_array(strtolower($rawStatus), ['update', 'consignment_update'])) ? $rawStatus : ($order->status ?: 'in_review'),
                'note' => $displayNote,
                'raw_note' => $note,
                'cod_amount' => $payout['cod_amount'],
                'delivery_charge' => $payout['delivery_charge'],
                'cod_charge' => $payout['cod_charge'],
                'receivable_amount' => $payout['net_receivable'],
                'timestamp' => $eventBdt->toDateTimeString(),
                'formatted_date' => $eventBdt->format('d M Y'),
                'formatted_time' => $eventBdt->format('h:i a'),
                'timezone' => 'BDT',
                'created_at' => $nowBdt->toDateTimeString(),
            ];
            $trackingHistory[] = $logEntry;
            $order->tracking = $trackingHistory;

            $hasStatusChanged = ($order->status !== $oldStatus);
            $hasPaymentChanged = ($order->payment_status !== $oldPaymentStatus);

            // 10. Update Order Edit History in update_history ONLY when status or payment actually changes
            if ($hasStatusChanged || $hasPaymentChanged) {
                $history = is_array($order->update_history) ? $order->update_history : [];
                $history[] = [
                    'action' => 'steadfast_webhook_sync',
                    'from_status' => $oldStatus,
                    'to_status' => $order->status,
                    'from_payment_status' => $oldPaymentStatus,
                    'to_payment_status' => $order->payment_status,
                    'courier_status' => $rawStatus,
                    'collected_amount' => $payout['cod_amount'],
                    'delivery_charge' => $payout['delivery_charge'],
                    'cod_charge' => $payout['cod_charge'],
                    'net_receivable' => $payout['net_receivable'],
                    'note' => $displayNote,
                    'updated_by' => 'Steadfast Webhook',
                    'timestamp' => $nowBdt->toDateTimeString(),
                ];
                $order->update_history = $history;
            }

            $order->save();

            // 11. Record Audit Log in OrderLog ONLY when status or payment_status actually changed!
            // Routine transit checkpoints are already displayed in the Courier Tracking Log timeline.
            if ($hasStatusChanged || $hasPaymentChanged) {
                try {
                    OrderLog::create([
                        'order_id' => $order->id,
                        'user_id' => null,
                        'action' => 'Steadfast: ' . ucfirst(str_replace(['_', '-'], ' ', $rawStatus ?: 'Status Update')),
                        'old_data' => [
                            'status' => $oldStatus,
                            'payment_status' => $oldPaymentStatus,
                        ],
                        'new_data' => [
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'steadfast_status' => $rawStatus,
                            'consignment_id' => $consignmentId,
                            'tracking_code' => $trackingCode,
                            'cod_amount' => $payout['cod_amount'],
                            'delivery_charge' => $payout['delivery_charge'],
                            'cod_charge' => $payout['cod_charge'],
                            'receivable_amount' => $payout['net_receivable'],
                            'note' => $displayNote,
                        ],
                    ]);
                } catch (Throwable $e) {
                    SteadfastLogger::warning('Failed to create OrderLog entry: ' . $e->getMessage());
                }
            }

            // 12. Send Telegram Notification on Delivery (No SMS or Mail to customer as requested!)
            if ($isDelivered && $oldStatus !== 'delivered') {
                try {
                    $telegramService = app(TelegramService::class);
                    if ($telegramService->isConfigured()) {
                        $shipping = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address ?? '[]', true);
                        $customerName = $shipping['name'] ?? optional($order->user)->name ?? 'Customer';
                        $customerPhone = $shipping['phone'] ?? optional($order->user)->phone ?? 'N/A';
                        $addressParts = array_filter([
                            $shipping['address'] ?? '',
                            $shipping['city'] ?? '',
                            $shipping['zone'] ?? ''
                        ]);
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
                    }
                } catch (Throwable $teleEx) {
                    SteadfastLogger::warning('Telegram delivery alert failed: ' . $teleEx->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Steadfast webhook processed successfully.',
                'order_id' => $order->order_id,
                'current_status' => $order->status,
                'current_payment_status' => $order->payment_status,
                'payout' => $payout,
            ]);

        } catch (Throwable $e) {
            // Absolute fail-safe: Catch all exceptions so the webhook never crashes
            SteadfastLogger::error("Steadfast Webhook Crash Caught: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An internal error occurred while processing the webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate Steadfast financial payout / receivable amount.
     *
     * Rules:
     * - Cash collected - Delivery charge = remainder
     * - COD charge (1%) deducted from remainder
     * - If COD amount is 0 (prepaid), profit is 0 (delivery charge is not deducted)
     * - If COD amount <= delivery charge, balance is negative so profit is 0
     *
     * @param float $codAmount
     * @param float $deliveryCharge
     * @return array
     */
    public static function calculatePayout(float $codAmount, float $deliveryCharge): array
    {
        $codAmount = max(0.0, round($codAmount, 2));
        $deliveryCharge = max(0.0, round($deliveryCharge, 2));

        // Rule 1: If COD is 0 (customer paid online/advance)
        if ($codAmount <= 0) {
            return [
                'cod_amount' => 0.0,
                'delivery_charge' => $deliveryCharge,
                'cod_charge' => 0.0,
                'net_receivable' => 0.0, // 0 profit
            ];
        }

        $diff = $codAmount - $deliveryCharge;

        // Rule 2: If COD is less than or equal to delivery charge (negative or zero balance)
        if ($diff <= 0) {
            return [
                'cod_amount' => $codAmount,
                'delivery_charge' => $deliveryCharge,
                'cod_charge' => 0.0,
                'net_receivable' => 0.0, // 0 profit
            ];
        }

        // Rule 3: 1% COD charge on remainder
        $codCharge = round($diff * 0.01);
        $netReceivable = round($diff - $codCharge);

        return [
            'cod_amount' => $codAmount,
            'delivery_charge' => $deliveryCharge,
            'cod_charge' => (float) $codCharge,
            'net_receivable' => (float) max(0.0, $netReceivable),
        ];
    }
}
