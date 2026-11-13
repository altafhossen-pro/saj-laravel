<?php

namespace App\Services;

use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderLog;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderStockService
{
    /**
     * Deduct stock for an order when it moves to 'confirmed' status.
     * Safe against double-deduction.
     *
     * @param Order $order
     * @param string|null $actor
     * @return array
     */
    public static function deductOrderStock(Order $order, ?string $actor = null): array
    {
        // Guard: do not deduct if already deducted
        if ($order->is_stock_deducted) {
            return [
                'success' => false,
                'already_deducted' => true,
                'message' => 'Stock has already been deducted for this order.'
            ];
        }

        $actor = $actor ?: (auth()->user()?->name ?? 'System/Admin');
        $deductedItems = [];

        DB::beginTransaction();
        try {
            $order->loadMissing(['items.product']);

            foreach ($order->items as $item) {
                $qty = (int) $item->quantity;
                if ($qty <= 0) continue;

                $variant = null;
                if (!empty($item->variant_sku)) {
                    $variant = ProductVariant::where('sku', $item->variant_sku)->first();
                    if (!$variant && $item->product) {
                        $variant = $item->product->variants->firstWhere('sku', $item->variant_sku);
                    }
                }

                $product = $item->product ?: Product::find($item->product_id);

                $prevVariantStock = null;
                $newVariantStock = null;
                if ($variant) {
                    $prevVariantStock = (int) $variant->stock_quantity;
                    $variant->decrement('stock_quantity', $qty);
                    $newVariantStock = (int) $variant->fresh()->stock_quantity;
                }

                $prevProductStock = null;
                $newProductStock = null;
                if ($product) {
                    $prevProductStock = (int) $product->total_stock;
                    $product->decrement('total_stock', $qty);
                    $product->increment('total_sold', $qty);
                    $newProductStock = (int) $product->fresh()->total_stock;
                }

                $deductedItems[] = [
                    'order_item_id' => $item->id,
                    'product_id' => $product?->id,
                    'product_name' => $item->name,
                    'variant_sku' => $item->variant_sku,
                    'quantity_deducted' => $qty,
                    'variant_stock_before' => $prevVariantStock,
                    'variant_stock_after' => $newVariantStock,
                    'product_stock_before' => $prevProductStock,
                    'product_stock_after' => $newProductStock,
                ];
            }

            $order->is_stock_deducted = true;
            $order->save();

            // Record in update_history
            $history = is_array($order->update_history) ? $order->update_history : [];
            $history[] = [
                'action' => 'stock_deducted_confirmed',
                'deducted_by' => $actor,
                'items_count' => count($deductedItems),
                'timestamp' => now()->setTimezone('Asia/Dhaka')->toDateTimeString(),
            ];
            $order->update_history = $history;
            $order->save();

            // Record detailed audit log
            OrderLog::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => 'Stock Deducted on Order Confirmation',
                'old_data' => ['is_stock_deducted' => false],
                'new_data' => [
                    'is_stock_deducted' => true,
                    'deducted_by' => $actor,
                    'items' => $deductedItems,
                ],
            ]);

            DB::commit();

            return [
                'success' => true,
                'items' => $deductedItems,
                'message' => 'Stock deducted successfully for ' . count($deductedItems) . ' item(s).'
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to deduct stock: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Restock items when an order is cancelled or returned.
     * Supports full restock or partial item/quantity restock.
     *
     * @param Order $order
     * @param array $restockSelections Array of ['item_id' => int, 'quantity' => int]
     * @param string $actionType 'cancel' or 'return'
     * @param string|null $reason
     * @param string|null $actor
     * @return array
     */
    public static function restockOrderItems(Order $order, array $restockSelections, string $actionType = 'cancel', ?string $reason = null, ?string $actor = null): array
    {
        $actor = $actor ?: (auth()->user()?->name ?? 'System/Admin');
        $order->loadMissing(['items.product']);

        // If stock was never deducted for this order (e.g. cancelled while still in pending)
        if (!$order->is_stock_deducted) {
            return [
                'success' => true,
                'restocked_count' => 0,
                'items' => [],
                'message' => 'Order was in Pending status; stock was never deducted, so no restock was needed.'
            ];
        }

        // Calculate already returned/restocked quantities per order_item_id to prevent over-restocking
        $existingReturns = is_array($order->return_quantities) ? $order->return_quantities : [];
        $alreadyRestocked = [];
        foreach ($existingReturns as $event) {
            if (!empty($event['items']) && is_array($event['items'])) {
                foreach ($event['items'] as $it) {
                    $id = $it['order_item_id'] ?? null;
                    $qty = (int) ($it['restocked_quantity'] ?? 0);
                    if ($id) {
                        $alreadyRestocked[$id] = ($alreadyRestocked[$id] ?? 0) + $qty;
                    }
                }
            }
        }

        $restockedItems = [];

        DB::beginTransaction();
        try {
            foreach ($restockSelections as $selection) {
                $itemId = $selection['item_id'] ?? null;
                $reqQty = (int) ($selection['quantity'] ?? 0);

                if (!$itemId || $reqQty <= 0) continue;

                $orderItem = $order->items->firstWhere('id', $itemId);
                if (!$orderItem) continue;

                // Max quantity that can be restocked for this item
                $maxAllowable = max(0, (int)$orderItem->quantity - ($alreadyRestocked[$itemId] ?? 0));
                $qtyToRestore = min($reqQty, $maxAllowable);

                if ($qtyToRestore <= 0) continue;

                $variant = null;
                if (!empty($orderItem->variant_sku)) {
                    $variant = ProductVariant::where('sku', $orderItem->variant_sku)->first();
                    if (!$variant && $orderItem->product) {
                        $variant = $orderItem->product->variants->firstWhere('sku', $orderItem->variant_sku);
                    }
                }

                $product = $orderItem->product ?: Product::find($orderItem->product_id);

                $prevVariantStock = null;
                $newVariantStock = null;
                if ($variant) {
                    $prevVariantStock = (int) $variant->stock_quantity;
                    $variant->increment('stock_quantity', $qtyToRestore);
                    $newVariantStock = (int) $variant->fresh()->stock_quantity;
                }

                $prevProductStock = null;
                $newProductStock = null;
                if ($product) {
                    $prevProductStock = (int) $product->total_stock;
                    $product->increment('total_stock', $qtyToRestore);
                    $product->decrement('total_sold', $qtyToRestore);
                    $newProductStock = (int) $product->fresh()->total_stock;
                }

                $restockedItems[] = [
                    'order_item_id' => $orderItem->id,
                    'product_id' => $product?->id,
                    'product_name' => $orderItem->name,
                    'variant_sku' => $orderItem->variant_sku,
                    'ordered_quantity' => (int) $orderItem->quantity,
                    'restocked_quantity' => $qtyToRestore,
                    'variant_stock_before' => $prevVariantStock,
                    'variant_stock_after' => $newVariantStock,
                    'product_stock_before' => $prevProductStock,
                    'product_stock_after' => $newProductStock,
                ];
            }

            if (!empty($restockedItems)) {
                // Record event in return_quantities JSON column
                $existingReturns[] = [
                    'event_id' => uniqid('rstk_'),
                    'action_type' => $actionType,
                    'reason' => $reason ?: 'Order ' . ucfirst($actionType),
                    'actor' => $actor,
                    'timestamp' => now()->setTimezone('Asia/Dhaka')->toDateTimeString(),
                    'items' => $restockedItems,
                ];
                $order->return_quantities = $existingReturns;

                // Record in update_history
                $history = is_array($order->update_history) ? $order->update_history : [];
                $history[] = [
                    'action' => 'stock_restocked_' . $actionType,
                    'actor' => $actor,
                    'reason' => $reason,
                    'restocked_count' => count($restockedItems),
                    'timestamp' => now()->setTimezone('Asia/Dhaka')->toDateTimeString(),
                ];
                $order->update_history = $history;
                $order->save();

                // Detailed OrderLog entry for tracking
                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'action' => 'Stock Restocked on ' . ucfirst($actionType),
                    'old_data' => [
                        'status' => $order->getOriginal('status'),
                    ],
                    'new_data' => [
                        'action_type' => $actionType,
                        'reason' => $reason,
                        'actor' => $actor,
                        'restocked_items' => $restockedItems,
                    ],
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'restocked_count' => count($restockedItems),
                'items' => $restockedItems,
                'message' => count($restockedItems) > 0 
                    ? 'Successfully restocked ' . count($restockedItems) . ' item(s) to inventory.' 
                    : 'No items eligible for restock.'
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to restock items: ' . $e->getMessage()
            ];
        }
    }
}
