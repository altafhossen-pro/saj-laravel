<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = [
        'type',
        'content',
        'mobile_content',
        'link',
        'sort_order',
        'is_active',
    ];
}
