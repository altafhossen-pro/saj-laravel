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
            $table->string('metrics_mode')->default('organic')->after('total_sold');
            $table->date('metrics_start_date')->nullable()->after('metrics_mode');
            $table->integer('metrics_daily_avg')->nullable()->after('metrics_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['metrics_mode', 'metrics_start_date', 'metrics_daily_avg']);
        });
    }
};
