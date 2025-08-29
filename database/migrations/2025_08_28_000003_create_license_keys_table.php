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
        Schema::create('license_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->string('key')->unique();
            $table->string('customer_email');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for common query patterns
            $table->index(['brand_id', 'is_active']);
            $table->index(['customer_email', 'brand_id']);
            $table->index('key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_keys');
    }
};
