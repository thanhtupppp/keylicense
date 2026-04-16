<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_ip_allowlists', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_key_id')->constrained('license_keys')->cascadeOnDelete();
            $table->string('cidr', 64);
            $table->string('label', 128)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['license_key_id', 'cidr']);
        });

        Schema::create('ip_blocklist', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('cidr', 64)->unique();
            $table->string('reason', 128)->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('plan_geo_restrictions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('restriction_type', 32);
            $table->json('country_codes');
            $table->timestamps();

            $table->index(['plan_id', 'restriction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_geo_restrictions');
        Schema::dropIfExists('ip_blocklist');
        Schema::dropIfExists('license_ip_allowlists');
    }
};
