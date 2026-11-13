<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'is_stock_deducted')) {
                $table->boolean('is_stock_deducted')->default(false)->after('status');
            }
        });

        // Mark previously completed/in-flight orders as already having stock deducted
        DB::table('orders')
            ->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered'])
            ->update(['is_stock_deducted' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'is_stock_deducted')) {
                $table->dropColumn('is_stock_deducted');
            }
        });
    }
};
