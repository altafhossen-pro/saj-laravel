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
        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_token', 80)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 40)->nullable()->index();
            $table->string('customer_email')->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('device', 40)->default('Desktop');
            $table->string('browser', 60)->nullable();
            $table->string('os', 60)->nullable();
            $table->string('referrer')->nullable();
            $table->string('entry_url')->nullable();
            $table->string('last_url')->nullable();
            $table->string('last_title')->nullable();
            $table->unsignedInteger('page_views_count')->default(1);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('has_cart')->default(false)->index();
            $table->boolean('visited_checkout')->default(false)->index();
            $table->boolean('is_returning')->default(false)->index();
            $table->date('session_date')->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();

            $table->index(['session_date', 'visitor_token']);
            $table->index(['session_date', 'has_cart']);
            $table->index(['session_date', 'visited_checkout']);
        });

        Schema::create('visitor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_session_id')->constrained('visitor_sessions')->cascadeOnDelete();
            $table->string('visitor_token', 80)->index();
            $table->string('event_type', 30)->default('page_view')->index(); // 'page_view', 'add_to_cart', 'checkout_visit', 'search'
            $table->string('url')->nullable();
            $table->string('title')->nullable();
            $table->json('metadata')->nullable(); // extra payload (product info, query, etc.)
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['visitor_session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_events');
        Schema::dropIfExists('visitor_sessions');
    }
};
