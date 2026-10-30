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
        Schema::create('offer_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Admin reference name (e.g. ফ্রি ডেলিভারি অফার)
            $table->string('title'); // Popup headline in Bengali
            $table->text('message'); // Popup message in Bengali
            $table->string('coupon_code', 50)->nullable();
            $table->string('button_text', 60)->default('অফারটি গ্রহণ করুন');
            $table->string('button_url', 255)->default('/checkout');
            $table->unsignedInteger('delay_seconds')->default(10);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_templates');
    }
};
