<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->index();
            $table->string('custom_subtitle')->nullable();
            $table->string('global_subtitle')->nullable();
            $table->boolean('is_global_subtitle_on')->default(false);
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('sub_categories')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->json('tags')->nullable();
            $table->enum('product_type', ['simple', 'variable', 'grouped', 'digital'])->default('simple');
            $table->boolean('exclude_from_category_discount')->default(false);
            $table->boolean('is_bracelet')->default(false);
            $table->boolean('is_ring')->default(false);
            $table->json('bracelet_sizes')->nullable();
            $table->json('ring_sizes')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('product_videos')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->json('price_range')->nullable();
            $table->json('specifications')->nullable();
            $table->json('available_attributes')->nullable();
            $table->integer('total_stock')->default(0);
            $table->enum('status', ['draft', 'published', 'archived', 'out_of_stock'])->default('published');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_force_out_of_stock')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestselling')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->json('shipping_info')->nullable();
            $table->json('seo')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->json('rating_breakdown')->nullable();
            $table->integer('total_sold')->default(0);
            $table->text('additional_info')->nullable();
            $table->string('announcement_text')->nullable();
            $table->text('delivery_info')->nullable();
            $table->text('return_policy')->nullable();
            $table->text('warranty_info')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
