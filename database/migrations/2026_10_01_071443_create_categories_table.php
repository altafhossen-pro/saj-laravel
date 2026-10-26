<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('image')->nullable();
            
            // Self referencing for subcategories
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('bg_class')->nullable();
            $table->integer('sort_order')->default(0);
            
            $table->boolean('show_on_header')->default(false);
            $table->integer('header_sort_order')->default(0);
            $table->boolean('show_child_as_sub_menu')->default(false);
            
            // JSON fields for complex Node.js settings
            $table->json('banner')->nullable();
            $table->json('category_discount')->nullable();
            $table->json('sku_settings')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
