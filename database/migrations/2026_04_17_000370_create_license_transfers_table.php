<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_transfers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_key_id')->constrained('license_keys')->cascadeOnDelete();
            $table->uuid('from_customer_id')->nullable()->index();
            $table->uuid('to_customer_id')->nullable()->index();
            $table->string('status', 32)->default('pending');
            $table->string('reason')->nullable();
            $table->timestampTz('transferred_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_transfers');
    }
};
