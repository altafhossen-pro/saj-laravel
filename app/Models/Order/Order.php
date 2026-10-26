<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'is_draft_mode' => 'boolean',
        'tracking' => 'array',
        'status_timestamps' => 'array',
        'is_gift' => 'boolean',
        'is_guest_order' => 'boolean',
        'guest_info' => 'array',
        'manual_order_info' => 'array',
        'return_quantities' => 'array',
        'update_history' => 'array',
        'affiliate_order' => 'array',
        'is_added_into_steadfast' => 'boolean',
        'is_read_by_admin' => 'boolean',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'steadfast_collected_amount' => 'decimal:2',
    ];

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the user that placed the order.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the logs for the order.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the overall combined discount (coupon, manual, upsell, loyalty).
     */
    public function getTotalDiscountAttribute(): float
    {
        return (float) ($this->discount ?? 0)
            + (float) ($this->coupon_discount ?? 0)
            + (float) ($this->upsell_discount ?? 0)
            + (float) ($this->loyalty_discount ?? 0);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_id)) {
                $model->order_id = 'TEMP-' . uniqid();
            }
        });

        static::created(function ($model) {
            if (str_starts_with($model->order_id, 'TEMP-')) {
                $attempts = 0;
                do {
                    // Up to 500k orders = 6 digits (900k space). Above 500k = 7 digits (9M space).
                    $digits = $model->id < 500000 ? 6 : ($model->id < 5000000 ? 7 : 8);
                    
                    // If random collisions happen frequently, bump digits to ensure instant unique generation
                    if ($attempts > 3) {
                        $digits++;
                    }

                    $min = (int) pow(10, $digits - 1);
                    $max = (int) pow(10, $digits) - 1;
                    $orderId = (string) mt_rand($min, $max);
                    $attempts++;
                } while (static::where('order_id', $orderId)->exists());

                $model->order_id = $orderId;
                $model->saveQuietly();
            }
        });
    }
}
