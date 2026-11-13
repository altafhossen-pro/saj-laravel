<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CustomerDeliveryRecord extends Model
{
    protected $table = 'customer_delivery_records';

    protected $fillable = [
        'phone',
        'delivery_ratio',
        'cancellation_ratio',
        'volume_band',
        'volume_range',
        'total_reports',
        'fraud_categories',
        'raw_response',
        'checked_at',
    ];

    protected $casts = [
        'delivery_ratio' => 'decimal:2',
        'cancellation_ratio' => 'decimal:2',
        'total_reports' => 'integer',
        'fraud_categories' => 'array',
        'raw_response' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Check if record is fresh (within 24 hours).
     */
    public function isFresh(): bool
    {
        if (!$this->checked_at) {
            return false;
        }

        return $this->checked_at->gt(now()->subHours(24));
    }

    /**
     * Format array for frontend display.
     */
    public function toDisplayArray(): array
    {
        return [
            'phone' => $this->phone,
            'delivery_ratio' => $this->delivery_ratio !== null ? (float) $this->delivery_ratio : null,
            'cancellation_ratio' => $this->cancellation_ratio !== null ? (float) $this->cancellation_ratio : null,
            'volume_band' => $this->volume_band,
            'volume_range' => $this->volume_range,
            'total_reports' => (int) $this->total_reports,
            'fraud_categories' => $this->fraud_categories ?? [],
            'checked_at' => $this->checked_at ? $this->checked_at->setTimezone('Asia/Dhaka')->format('d M Y, h:i a') : null,
            'is_fresh' => $this->isFresh(),
        ];
    }
}
