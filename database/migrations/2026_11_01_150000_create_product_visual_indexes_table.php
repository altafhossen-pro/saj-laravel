<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_visual_indexes')) {
            Schema::create('product_visual_indexes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('image_path');
                $table->boolean('is_featured')->default(true);
                $table->string('dhash', 64)->index();
                $table->string('ahash', 64)->index();
                $table->json('color_signature')->nullable();
                $table->decimal('aspect_ratio', 4, 2)->default(1.00);
                $table->timestamps();

                $table->index(['product_id', 'is_featured']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_visual_indexes');
    }
};

