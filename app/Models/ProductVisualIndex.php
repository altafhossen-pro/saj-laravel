<?php

namespace App\Models;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVisualIndex extends Model
{
    use HasFactory;

    protected $table = 'product_visual_indexes';

    protected $fillable = [
        'product_id',
        'variant_id',
        'image_path',
        'is_featured',
        'source_type',
        'dhash',
        'dhash_detail',
        'ahash',
        'center_dhash',
        'color_signature',
        'aspect_ratio',
        'search_text',
        'ai_embedding',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'color_signature' => 'array',
        'ai_embedding' => 'array',
        'aspect_ratio' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product\ProductVariant::class, 'variant_id');
    }
}

