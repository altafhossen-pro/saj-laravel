<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_token',
        'daily_serial',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_email',
        'ip_address',
        'device',
        'browser',
        'os',
        'referrer',
        'entry_url',
        'last_url',
        'last_title',
        'page_views_count',
        'duration_seconds',
        'has_cart',
        'visited_checkout',
        'is_returning',
        'session_date',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'daily_serial' => 'integer',
        'page_views_count' => 'integer',
        'duration_seconds' => 'integer',
        'has_cart' => 'boolean',
        'visited_checkout' => 'boolean',
        'is_returning' => 'boolean',
        'session_date' => 'date',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisitorEvent::class, 'visitor_session_id')->orderBy('id', 'asc');
    }

    public function cartSession(): BelongsTo
    {
        return $this->belongsTo(CartSession::class, 'visitor_token', 'visitor_token');
    }

    public function customOffers(): HasMany
    {
        return $this->hasMany(VisitorCustomOffer::class, 'visitor_token', 'visitor_token')->latest();
    }

    public function getFormattedDurationAttribute(): string
    {
        $sec = (int) $this->duration_seconds;
        if ($sec <= 0 && $this->first_seen_at && $this->last_seen_at) {
            $sec = max(0, $this->last_seen_at->timestamp - $this->first_seen_at->timestamp);
        }

        if ($sec < 60) {
            return $sec . 's';
        }
        $min = floor($sec / 60);
        $remSec = $sec % 60;
        return "{$min}m {$remSec}s";
    }
}

