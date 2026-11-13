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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'steadfast_delivery_charge')) {
                $table->decimal('steadfast_delivery_charge', 10, 2)->default(0)->after('steadfast_collected_amount');
            }
            if (!Schema::hasColumn('orders', 'steadfast_cod_charge')) {
                $table->decimal('steadfast_cod_charge', 10, 2)->default(0)->after('steadfast_delivery_charge');
            }
            if (!Schema::hasColumn('orders', 'steadfast_receivable_amount')) {
                $table->decimal('steadfast_receivable_amount', 10, 2)->default(0)->after('steadfast_cod_charge');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('orders', 'steadfast_delivery_charge')) {
                $columns[] = 'steadfast_delivery_charge';
            }
            if (Schema::hasColumn('orders', 'steadfast_cod_charge')) {
                $columns[] = 'steadfast_cod_charge';
            }
            if (Schema::hasColumn('orders', 'steadfast_receivable_amount')) {
                $columns[] = 'steadfast_receivable_amount';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
