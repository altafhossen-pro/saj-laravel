<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'target_ids' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'usage_limit_total' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'first_order_only' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is currently active and within valid date range and total limits.
     */
    public function isValidNow(): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'This coupon is currently inactive.'];
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return ['valid' => false, 'message' => 'This coupon is not active yet. Starts on ' . $this->starts_at->format('M d, Y')];
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return ['valid' => false, 'message' => 'This coupon has expired on ' . $this->expires_at->format('M d, Y')];
        }

        if ($this->usage_limit_total !== null && $this->used_count >= $this->usage_limit_total) {
            return ['valid' => false, 'message' => 'This coupon has reached its maximum total usage limit.'];
        }

        return ['valid' => true, 'message' => 'Valid'];
    }

    /**
     * Check if a specific user/phone can use this coupon based on per-user limits and first-order rules.
     */
    public function canUserUse($userId = null, $phone = null): array
    {
        $status = $this->isValidNow();
        if (!$status['valid']) {
            return $status;
        }

        // First order check
        if ($this->first_order_only) {
            $hasPreviousOrders = false;
            if ($userId) {
                $hasPreviousOrders = Order::where('user_id', $userId)
                    ->where('status', '!=', 'cancelled')
                    ->where('is_draft_mode', false)
                    ->exists();
            } elseif ($phone) {
                $hasPreviousOrders = Order::where(function ($q) use ($phone) {
                    $q->whereJsonContains('shipping_address->phone', $phone)
                      ->orWhereJsonContains('guest_info->phone', $phone);
                })
                ->where('status', '!=', 'cancelled')
                ->where('is_draft_mode', false)
                ->exists();
            }

            if ($hasPreviousOrders) {
                return ['valid' => false, 'message' => 'This coupon is only valid for first-time orders.'];
            }
        }

        // Per user usage limit check
        if ($this->usage_limit_per_user > 0) {
            $userUsageCount = 0;

            if ($userId) {
                $userUsageCount = $this->usages()->where('user_id', $userId)->count();
            }

            if ($phone) {
                $phoneUsageCount = $this->usages()->where('customer_phone', $phone)->count();
                $userUsageCount = max($userUsageCount, $phoneUsageCount);
            }

            if ($userUsageCount >= $this->usage_limit_per_user) {
                return [
                    'valid' => false,
                    'message' => "You have already used this coupon the maximum allowed times ({$this->usage_limit_per_user})."
                ];
            }
        }

        return ['valid' => true, 'message' => 'Eligible'];
    }

    /**
     * Calculate discount amount against cart items, subtotal, and shipping cost.
     */
    public function calculateDiscount(array $cartItems, float $subtotal, float $shippingCost = 0, $userId = null, $phone = null): array
    {
        $eligibility = $this->canUserUse($userId, $phone);
        if (!$eligibility['valid']) {
            return ['valid' => false, 'discount' => 0, 'message' => $eligibility['message']];
        }

        if ($this->min_order_amount !== null && $subtotal < (float)$this->min_order_amount) {
            return [
                'valid' => false,
                'discount' => 0,
                'message' => "Minimum order amount of ৳" . number_format($this->min_order_amount, 2) . " required for this coupon."
            ];
        }

        $eligibleSubtotal = 0;
        $targetIds = is_array($this->target_ids) ? $this->target_ids : [];

        if ($this->target_type === 'category' && !empty($targetIds)) {
            // Collect all allowed category IDs including all descendants (sub-categories)
            $allowedCategoryIds = [];
            foreach ($targetIds as $catId) {
                $cat = Category::find($catId);
                if ($cat) {
                    $allowedCategoryIds = array_merge($allowedCategoryIds, $cat->getAllDescendantIds());
                } else {
                    $allowedCategoryIds[] = (int) $catId;
                }
            }
            $allowedCategoryIds = array_unique(array_map('intval', $allowedCategoryIds));

            foreach ($cartItems as $item) {
                $productId = $item['product_id'] ?? ($item['id'] ?? null);
                if ($productId) {
                    $product = Product::find($productId);
                    if ($product && in_array((int)$product->category_id, $allowedCategoryIds)) {
                        $itemPrice = (float)($item['price'] ?? 0);
                        $itemQty = (int)($item['quantity'] ?? 1);
                        $eligibleSubtotal += ($itemPrice * $itemQty);
                    }
                }
            }

            if ($eligibleSubtotal <= 0) {
                return [
                    'valid' => false,
                    'discount' => 0,
                    'message' => 'This coupon is not applicable to any products in your cart.'
                ];
            }
        } elseif ($this->target_type === 'product' && !empty($targetIds)) {
            $allowedProductIds = array_map('intval', $targetIds);

            foreach ($cartItems as $item) {
                $productId = (int) ($item['product_id'] ?? ($item['id'] ?? 0));
                if (in_array($productId, $allowedProductIds)) {
                    $itemPrice = (float)($item['price'] ?? 0);
                    $itemQty = (int)($item['quantity'] ?? 1);
                    $eligibleSubtotal += ($itemPrice * $itemQty);
                }
            }

            if ($eligibleSubtotal <= 0) {
                return [
                    'valid' => false,
                    'discount' => 0,
                    'message' => 'This coupon is only applicable to specific items which are not in your cart.'
                ];
            }
        } else {
            // Entire store / all products
            $eligibleSubtotal = $subtotal;
        }

        // Calculate discount based on type
        $discount = 0;

        if ($this->discount_type === 'free_shipping') {
            $discount = max(0, $shippingCost);
        } elseif ($this->discount_type === 'fixed') {
            $discount = min((float)$this->discount_amount, $eligibleSubtotal);
        } elseif ($this->discount_type === 'percentage') {
            $discount = ($eligibleSubtotal * (float)$this->discount_amount) / 100;
            if ($this->max_discount_amount !== null && $discount > (float)$this->max_discount_amount) {
                $discount = (float)$this->max_discount_amount;
            }
        }

        $discount = round(min($discount, $subtotal + ($this->discount_type === 'free_shipping' ? $shippingCost : 0)), 2);

        return [
            'valid' => true,
            'discount' => $discount,
            'discount_type' => $this->discount_type,
            'discount_amount' => (float)$this->discount_amount,
            'code' => $this->code,
            'message' => 'Coupon applied successfully!'
        ];
    }
}
