<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            $table->string('sku')->unique()->index();
            $table->string('barcode')->nullable();
            
            // JSON array of attributes for this variant (e.g. Size, Color)
            $table->json('attributes')->nullable(); 
            
            // Pricing
            $table->decimal('current_price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            
            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'low_stock', 'pre_order'])->default('in_stock');
            
            // Shipping Details
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('dimensions')->nullable(); // length, width, height
            
            $table->json('images')->nullable();
            $table->integer('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
