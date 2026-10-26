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
        Schema::table('search_logs', function (Blueprint $table) {
            // High-cardinality composite indexes for instant date-range aggregations
            $table->index(['created_at', 'source'], 'idx_search_created_source');
            $table->index(['user_id', 'created_at'], 'idx_search_user_created');
            $table->index(['clicked_product_id', 'created_at'], 'idx_search_product_created');
            $table->index(['ip_address', 'created_at'], 'idx_search_ip_created');
        });

        Schema::table('product_view_stats', function (Blueprint $table) {
            $table->index(['view_date', 'views_count'], 'idx_pviews_date_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_view_stats', function (Blueprint $table) {
            $table->dropIndex('idx_pviews_date_count');
        });

        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropIndex('idx_search_created_source');
            $table->dropIndex('idx_search_user_created');
            $table->dropIndex('idx_search_product_created');
            $table->dropIndex('idx_search_ip_created');
        });
    }
};
