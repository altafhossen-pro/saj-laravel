<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorCustomOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_token',
        'user_id',
        'customer_phone',
        'title',
        'message',
        'coupon_code',
        'button_text',
        'button_url',
        'delay_seconds',
        'status',
        'show_mode',
        'repeat_interval_minutes',
        'display_count',
        'admin_name',
        'delivered_at',
        'action_at',
    ];

    protected $casts = [
        'delay_seconds' => 'integer',
        'repeat_interval_minutes' => 'integer',
        'display_count' => 'integer',
        'delivered_at' => 'datetime',
        'action_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

