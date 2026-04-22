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
        Schema::create('floating_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->onDelete('cascade');
            $table->foreignId('activation_id')->constrained('activations')->onDelete('cascade');
            $table->char('device_fp_hash', 64);
            $table->timestamp('last_heartbeat_at');
            $table->timestamps();

            // Unique constraint
            $table->unique(['license_id', 'device_fp_hash']);

            // Indexes
            $table->index(['license_id', 'last_heartbeat_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floating_seats');
    }
};
