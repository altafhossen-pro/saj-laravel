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
        Schema::create('visitor_custom_offers', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_token', 80)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_phone', 40)->nullable()->index();
            $table->string('title');
            $table->text('message');
            $table->string('coupon_code', 50)->nullable();
            $table->string('button_text', 60)->nullable()->default('Claim Offer');
            $table->string('button_url')->nullable()->default('/checkout');
            $table->unsignedInteger('delay_seconds')->default(10); // delay before popup opens in seconds
            $table->string('status', 20)->default('pending')->index(); // 'pending', 'delivered', 'clicked', 'dismissed'
            $table->string('admin_name', 80)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();

            $table->index(['visitor_token', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_custom_offers');
    }
};
