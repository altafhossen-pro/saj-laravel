<?php

namespace App\Models;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisualSearchLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_rejected' => 'boolean',
        'top_score' => 'float',
        'results_count' => 'integer',
        'latency_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'top_product_id');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (empty($this->thumbnail_path)) {
            return null;
        }

        if (str_starts_with($this->thumbnail_path, 'http')) {
            return $this->thumbnail_path;
        }

        return asset('storage/' . ltrim($this->thumbnail_path, '/'));
    }
}

