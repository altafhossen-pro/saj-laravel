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
        Schema::table('cart_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('cart_sessions', 'info_history')) {
                $table->json('info_history')->nullable()->after('customer_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('cart_sessions', 'info_history')) {
                $table->dropColumn('info_history');
            }
        });
    }
};
