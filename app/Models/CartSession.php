<?php

namespace App\Models;

use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_token',
        'user_id',
        'customer_name',
        'customer_phone',
        'items',
        'total_items',
        'total_amount',
        'status',
        'order_id',
        'ip_address',
        'user_agent',
        'last_activity_at',
    ];

    protected $casts = [
        'items' => 'array',
        'total_items' => 'integer',
        'total_amount' => 'decimal:2',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function getCustomerDisplayNameAttribute(): string
    {
        if ($this->customer_name) {
            return $this->customer_name;
        }

        if ($this->user) {
            return $this->user->name;
        }

        return 'Guest Visitor';
    }

    public function getCustomerDisplayPhoneAttribute(): ?string
    {
        if ($this->customer_phone) {
            return $this->customer_phone;
        }

        if ($this->user && $this->user->phone) {
            return $this->user->phone;
        }

        return null;
    }

    public function toApiArray(): array
    {
        $phone = $this->customer_display_phone;
        $name = $this->customer_display_name;
        $items = is_array($this->items) ? $this->items : [];

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone ?: '');
        if (strlen($cleanPhone) === 11 && str_starts_with($cleanPhone, '01')) {
            $cleanPhone = '88' . $cleanPhone;
        }

        $firstItemName = !empty($items[0]['name']) ? $items[0]['name'] : 'selected products';
        $siteName = \App\Models\Setting::get('site_name', 'Ario Mart');
        $waMessage = urlencode("Hello {$name}, we noticed you left \"{$firstItemName}\" in your cart at {$siteName}. Would you like any assistance or a special discount to complete your order?");
        $waLink = $cleanPhone ? "https://wa.me/{$cleanPhone}?text={$waMessage}" : null;

        return [
            'id' => $this->id,
            'visitor_token' => $this->visitor_token,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'is_user' => (bool) $this->user_id,
            'items' => $items,
            'total_items' => (int) $this->total_items,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'order_id' => $this->order?->order_id ?? ($this->order_id ?: null),
            'order_url' => $this->order ? route('admin.orders.show', $this->order->order_id ?? $this->order->id) : null,
            'last_activity_human' => $this->last_activity_at ? $this->last_activity_at->diffForHumans() : $this->updated_at->diffForHumans(),
            'last_activity_formatted' => $this->last_activity_at ? $this->last_activity_at->format('d M, h:i A') : $this->updated_at->format('d M, h:i A'),
            'last_activity_timestamp' => $this->last_activity_at ? $this->last_activity_at->timestamp : $this->updated_at->timestamp,
            'wa_link' => $waLink,
            'delete_url' => route('admin.carts.destroy', $this->id),
        ];
    }
}
