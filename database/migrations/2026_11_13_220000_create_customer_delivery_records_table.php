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
        if (!Schema::hasTable('customer_delivery_records')) {
            Schema::create('customer_delivery_records', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 25)->unique()->index();
                $table->decimal('delivery_ratio', 5, 2)->nullable();
                $table->decimal('cancellation_ratio', 5, 2)->nullable();
                $table->string('volume_band', 50)->nullable();
                $table->string('volume_range', 50)->nullable();
                $table->integer('total_reports')->default(0);
                $table->json('fraud_categories')->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamp('checked_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_delivery_records');
    }
};
