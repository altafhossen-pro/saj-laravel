<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_visual_indexes')) {
            Schema::table('product_visual_indexes', function (Blueprint $table) {
                if (!Schema::hasColumn('product_visual_indexes', 'source_type')) {
                    $table->string('source_type', 32)->default('featured')->after('is_featured');
                }
                if (!Schema::hasColumn('product_visual_indexes', 'variant_id')) {
                    $table->unsignedBigInteger('variant_id')->nullable()->after('product_id')->index();
                }
                if (!Schema::hasColumn('product_visual_indexes', 'dhash_detail')) {
                    $table->string('dhash_detail', 256)->nullable()->after('dhash');
                }
                if (!Schema::hasColumn('product_visual_indexes', 'center_dhash')) {
                    $table->string('center_dhash', 64)->nullable()->after('ahash');
                }
                if (!Schema::hasColumn('product_visual_indexes', 'search_text')) {
                    $table->text('search_text')->nullable()->after('aspect_ratio');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_visual_indexes')) {
            Schema::table('product_visual_indexes', function (Blueprint $table) {
                $columns = ['source_type', 'variant_id', 'dhash_detail', 'center_dhash', 'search_text'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('product_visual_indexes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
