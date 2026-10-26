<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'parent_id',
        'is_active',
        'is_featured',
        'bg_class',
        'sort_order',
        'show_on_header',
        'header_sort_order',
        'show_child_as_sub_menu',
        'banner',
        'category_discount',
        'sku_settings',
        'show_on_homepage',
        'homepage_layout',
        'homepage_max_products',
        'homepage_per_page',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'show_on_header' => 'boolean',
        'show_child_as_sub_menu' => 'boolean',
        'banner' => 'array',
        'category_discount' => 'array',
        'sku_settings' => 'array',
        'show_on_homepage' => 'boolean',
        'homepage_max_products' => 'integer',
        'homepage_per_page' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(\App\Models\Product\Product::class);
    }

    public function getAllDescendantIds()
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('category_descendants_' . $this->id, function () {
            $ids = [$this->id];
            // Eager load children to minimize queries if cache is missed
            $category = Category::with('children')->find($this->id);
            if ($category) {
                foreach ($category->children as $child) {
                    $ids = array_merge($ids, $child->getAllDescendantIds());
                }
            }
            return $ids;
        });
    }

    protected static function booted()
    {
        static::saved(function ($category) {
            \Illuminate\Support\Facades\Cache::forget('sidebar_categories');
            \Illuminate\Support\Facades\Cache::forget('category_descendants_' . $category->id);
            \Illuminate\Support\Facades\Cache::forget('category_slug_' . $category->slug);
            if ($category->isDirty('slug') && $category->getOriginal('slug')) {
                \Illuminate\Support\Facades\Cache::forget('category_slug_' . $category->getOriginal('slug'));
            }
            if ($category->parent_id) {
                \Illuminate\Support\Facades\Cache::forget('category_descendants_' . $category->parent_id);
            }
        });

        static::deleted(function ($category) {
            \Illuminate\Support\Facades\Cache::forget('sidebar_categories');
            \Illuminate\Support\Facades\Cache::forget('category_descendants_' . $category->id);
            \Illuminate\Support\Facades\Cache::forget('category_slug_' . $category->slug);
            if ($category->parent_id) {
                \Illuminate\Support\Facades\Cache::forget('category_descendants_' . $category->parent_id);
            }
        });
    }
}
