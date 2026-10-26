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
        // 1. Search logs (Keyword, source direct/suggestion, clicked product, user, IP, hits)
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 255)->index();
            $table->enum('source', ['direct', 'suggestion'])->default('direct')->index();
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('hits_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'query']);
        });

        // 2. Add hidden lifetime views_count column to products if not exists
        if (!Schema::hasColumn('products', 'views_count')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('views_count')->default(0)->after('total_sold');
                $table->index('views_count');
            });
        }

        // 3. Ultra-fast aggregate table for date/time view analytics without table bloat
        Schema::create('product_view_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->date('view_date')->index();
            $table->unsignedInteger('views_count')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'view_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_view_stats');
        
        if (Schema::hasColumn('products', 'views_count')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['views_count']);
                $table->dropColumn('views_count');
            });
        }

        Schema::dropIfExists('search_logs');
    }
};
