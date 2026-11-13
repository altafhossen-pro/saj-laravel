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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_offer_active')->default(false)->after('base_price');
            $table->string('offer_title')->nullable()->after('is_offer_active');
            $table->string('offer_discount_type')->default('percentage')->nullable()->after('offer_title');
            $table->decimal('offer_discount_value', 10, 2)->nullable()->after('offer_discount_type');
            $table->decimal('offer_regular_price', 10, 2)->nullable()->after('offer_discount_value');
            $table->dateTime('offer_start_date')->nullable()->after('offer_regular_price');
            $table->dateTime('offer_end_date')->nullable()->after('offer_start_date');
            $table->json('offer_variant_prices')->nullable()->after('offer_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_offer_active',
                'offer_title',
                'offer_discount_type',
                'offer_discount_value',
                'offer_regular_price',
                'offer_start_date',
                'offer_end_date',
                'offer_variant_prices',
            ]);
        });
    }
};
