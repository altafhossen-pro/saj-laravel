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
        Schema::table('visitor_custom_offers', function (Blueprint $table) {
            $table->string('show_mode', 30)->default('once')->after('status'); // 'once', 'every_session'
            $table->unsignedInteger('repeat_interval_minutes')->default(60)->after('show_mode'); // Show again after X minutes if every_session
            $table->unsignedInteger('display_count')->default(0)->after('repeat_interval_minutes');
        });

        Schema::table('offer_templates', function (Blueprint $table) {
            $table->string('show_mode', 30)->default('once')->after('delay_seconds');
            $table->unsignedInteger('repeat_interval_minutes')->default(60)->after('show_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_custom_offers', function (Blueprint $table) {
            $table->dropColumn(['show_mode', 'repeat_interval_minutes', 'display_count']);
        });

        Schema::table('offer_templates', function (Blueprint $table) {
            $table->dropColumn(['show_mode', 'repeat_interval_minutes']);
        });
    }
};
