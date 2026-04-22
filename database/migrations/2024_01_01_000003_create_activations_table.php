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
            $table->foreignId('license_id')->constrained('licenses')->onDelete('cascade');
            $table->char('device_fp_hash', 64)->nullable();
            $table->string('user_identifier', 255)->nullable();
            $table->enum('type', ['per-device', 'per-user', 'floating']);
            $table->timestamp('activated_at');
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraints
            $table->unique(['license_id', 'device_fp_hash']);
            $table->unique(['license_id', 'user_identifier']);

            // Indexes
            $table->index(['license_id', 'is_active']);
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
