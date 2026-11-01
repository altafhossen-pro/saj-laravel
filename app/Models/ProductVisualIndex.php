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
        'image_path',
        'is_featured',
        'dhash',
        'ahash',
        'color_signature',
        'aspect_ratio',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'color_signature' => 'array',
        'aspect_ratio' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
