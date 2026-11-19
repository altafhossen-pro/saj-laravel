<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'attachments' => 'array',
        'is_custom' => 'boolean',
    ];

    public function getReviewerNameAttribute(): string
    {
        if (!empty($this->customer_name)) {
            return $this->customer_name;
        }
        return $this->user?->name ?? 'Customer';
    }

    public function getReviewerAvatarAttribute(): ?string
    {
        if (!empty($this->customer_avatar)) {
            return \Illuminate\Support\Facades\Storage::url($this->customer_avatar);
        }
        if (!empty($this->user?->avatar)) {
            return \Illuminate\Support\Facades\Storage::url($this->user->avatar);
        }
        return null;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
