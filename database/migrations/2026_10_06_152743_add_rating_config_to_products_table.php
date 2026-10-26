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
            $table->date('rating_metrics_start_date')->nullable()->after('rating_metrics_mode');
            $table->integer('rating_metrics_interval_days')->default(7)->after('rating_metrics_start_date');
            $table->integer('rating_metrics_count')->default(1)->after('rating_metrics_interval_days');
            $table->decimal('rating_metrics_min', 3, 2)->default(4.5)->after('rating_metrics_count');
            $table->decimal('rating_metrics_max', 3, 2)->default(5.0)->after('rating_metrics_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'rating_metrics_start_date',
                'rating_metrics_interval_days',
                'rating_metrics_count',
                'rating_metrics_min',
                'rating_metrics_max'
            ]);
        });
    }
};
