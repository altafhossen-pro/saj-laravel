<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_guest_order' => 'boolean',
        'is_draft_mode' => 'boolean',
        'is_gift' => 'boolean',
        'is_added_into_steadfast' => 'boolean',
        'is_read_by_admin' => 'boolean',
        'guest_info' => 'array',
        'manual_order_info' => 'array',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'status_timestamps' => 'array',
        'tracking' => 'array',
        'return_quantities' => 'array',
        'update_history' => 'array',
        'affiliate_order' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
