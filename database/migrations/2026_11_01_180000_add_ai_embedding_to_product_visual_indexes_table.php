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
                if (!Schema::hasColumn('product_visual_indexes', 'ai_embedding')) {
                    $table->longText('ai_embedding')->nullable()->after('color_signature');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_visual_indexes')) {
            Schema::table('product_visual_indexes', function (Blueprint $table) {
                if (Schema::hasColumn('product_visual_indexes', 'ai_embedding')) {
                    $table->dropColumn('ai_embedding');
                }
            });
        }
    }
};

