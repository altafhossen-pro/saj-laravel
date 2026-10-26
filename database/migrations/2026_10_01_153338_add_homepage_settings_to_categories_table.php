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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_on_homepage')->default(false)->after('is_active');
            $table->string('homepage_layout')->default('grid')->after('show_on_homepage');
            $table->integer('homepage_max_products')->default(100)->after('homepage_layout');
            $table->integer('homepage_per_page')->default(20)->after('homepage_max_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'show_on_homepage',
                'homepage_layout',
                'homepage_max_products',
                'homepage_per_page',
            ]);
        });
    }
};
