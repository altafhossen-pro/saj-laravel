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
        Schema::create('blacklists', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['ip', 'phone'])->index(); // 'ip' (single or CIDR) or 'phone' (11-digit)
            $table->string('value', 64)->index(); // IP address (e.g. 103.14.25.12, 192.168.1.0/24) or Phone (017xxxxxxxx)
            $table->string('reason')->nullable();
            $table->text('custom_message')->nullable(); // Dynamic message shown on client side
            $table->dateTime('expires_at')->nullable()->index(); // Null = Permanent ban, or specific expiration timestamp
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacklists');
    }
};
