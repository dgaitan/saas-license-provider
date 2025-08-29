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
        Schema::create('activations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('license_id')->constrained()->onDelete('cascade');
            $table->string('instance_id')->nullable();
            $table->string('instance_type')->nullable();
            $table->string('instance_url')->nullable();
            $table->string('machine_id')->nullable();
            $table->enum('status', ['active', 'deactivated', 'expired'])->default('active');
            $table->timestamp('activated_at');
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            // Indexes for common query patterns
            $table->index(['license_id', 'status']);
            $table->index(['instance_id', 'license_id']);
            $table->index(['instance_url', 'license_id']);
            $table->index(['machine_id', 'license_id']);
            $table->index('status');
            $table->index('activated_at');
            $table->index('deactivated_at');

            // Unique constraint to prevent duplicate activations for the same instance
            $table->unique(['license_id', 'instance_id'], 'unique_license_instance');
            $table->unique(['license_id', 'instance_url'], 'unique_license_url');
            $table->unique(['license_id', 'machine_id'], 'unique_license_machine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activations');
    }
};
