<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique()->index(); // The 6 digit custom order ID
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Addresses
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            
            // Statuses
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'])->default('pending')->index();
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Amounts
            $table->decimal('total', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('upsell_discount', 10, 2)->default(0);
            $table->decimal('loyalty_discount', 10, 2)->default(0);
            $table->decimal('loyalty_points_used', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            
            $table->text('order_notes')->nullable();
            $table->boolean('is_draft_mode')->default(false);
            
            $table->json('tracking')->nullable();
            $table->json('status_timestamps')->nullable();
            
            // Coupons and Gifts
            $table->string('coupon')->nullable();
            $table->decimal('coupon_discount', 10, 2)->default(0);
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->boolean('is_gift')->default(false);
            $table->text('gift_message')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Source & Meta
            $table->enum('order_type', ['auto', 'manual'])->default('auto');
            $table->enum('order_source', ['website', 'facebook', 'whatsapp', 'phone', 'email', 'walk-in', 'instagram', 'manual', 'other'])->default('website');
            $table->boolean('is_guest_order')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            
            $table->json('guest_info')->nullable();
            $table->json('manual_order_info')->nullable();
            $table->json('return_quantities')->nullable();
            $table->json('update_history')->nullable();
            $table->json('affiliate_order')->nullable();
            
            // Steadfast integration
            $table->boolean('is_added_into_steadfast')->default(false);
            $table->string('steadfast_consignment_id')->nullable();
            $table->string('steadfast_tracking_code')->nullable();
            $table->decimal('steadfast_collected_amount', 10, 2)->default(0);
            $table->boolean('is_read_by_admin')->default(false);
            
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
