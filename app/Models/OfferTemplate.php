<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'message',
        'coupon_code',
        'button_text',
        'button_url',
        'delay_seconds',
        'show_mode',
        'repeat_interval_minutes',
        'is_active',
    ];

    protected $casts = [
        'delay_seconds' => 'integer',
        'repeat_interval_minutes' => 'integer',
        'is_active' => 'boolean',
    ];
}

