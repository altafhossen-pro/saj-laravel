<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visual_search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('thumbnail_path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('detected_brand', 100)->nullable();
            $table->string('detected_type', 100)->nullable();
            $table->text('detected_keywords')->nullable();
            $table->boolean('is_rejected')->default(false);
            $table->foreignId('top_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('top_score', 5, 2)->nullable();
            $table->unsignedInteger('results_count')->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('is_rejected');
            $table->index('detected_brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visual_search_logs');
    }
};

