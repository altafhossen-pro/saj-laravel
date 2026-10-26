<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;

class ProductQuestion extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'customer_name',
        'phone',
        'question',
        'answer',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $appends = ['time_ago', 'answer_time_ago'];

    public function getTimeAgoAttribute()
    {
        return $this->created_at ? $this->created_at->diffForHumans() : '';
    }

    public function getAnswerTimeAgoAttribute()
    {
        return $this->updated_at ? $this->updated_at->diffForHumans() : '';
    }
}
