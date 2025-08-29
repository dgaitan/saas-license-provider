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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('license_key_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['valid', 'suspended', 'cancelled', 'expired'])->default('valid');
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_seats')->nullable();
            $table->timestamps();

            // Indexes for common query patterns
            $table->index(['license_key_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('expires_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
