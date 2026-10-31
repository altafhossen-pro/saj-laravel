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
        Schema::table('visitor_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('visitor_sessions', 'daily_serial')) {
                $table->unsignedInteger('daily_serial')->default(0)->after('visitor_token')->index();
            }
            if (!Schema::hasColumn('visitor_sessions', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('customer_phone');
            }
        });

        Schema::table('cart_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('cart_sessions', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('customer_phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('visitor_sessions', 'daily_serial')) {
                $table->dropColumn('daily_serial');
            }
            if (Schema::hasColumn('visitor_sessions', 'customer_address')) {
                $table->dropColumn('customer_address');
            }
        });

        Schema::table('cart_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('cart_sessions', 'customer_address')) {
                $table->dropColumn('customer_address');
            }
        });
    }
};
