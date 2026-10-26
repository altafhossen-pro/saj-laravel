<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;

class SearchLog extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clickedProduct()
    {
        return $this->belongsTo(Product::class, 'clicked_product_id');
    }
}
