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
        Schema::table('buying_guides', function (Blueprint $table) {
            $table->boolean('is_ai_generated')->default(false)->after('is_published')->index();
            $table->json('ai_metadata')->nullable()->after('is_ai_generated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buying_guides', function (Blueprint $table) {
            $table->dropColumn(['is_ai_generated', 'ai_metadata']);
        });
    }
};
