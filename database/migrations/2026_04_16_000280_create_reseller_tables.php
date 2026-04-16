<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 128)->unique();
            $table->string('contact_email', 255);
            $table->string('commission_type', 32)->default('percent');
            $table->unsignedInteger('commission_value')->default(0);
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('reseller_plans', function (Blueprint $table): void {
            $table->foreignUuid('reseller_id')->constrained('resellers')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->unsignedInteger('custom_price_cents')->nullable();
            $table->unsignedInteger('max_keys')->nullable();
            $table->primary(['reseller_id', 'plan_id']);
        });

        Schema::create('reseller_key_pools', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('reseller_id')->constrained('resellers')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->unsignedInteger('total_keys');
            $table->unsignedInteger('used_keys')->default(0);
            $table->timestampTz('expires_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reseller_key_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pool_id')->constrained('reseller_key_pools')->cascadeOnDelete();
            $table->foreignUuid('license_key_id')->constrained('license_keys')->cascadeOnDelete();
            $table->string('assigned_to_email', 255)->nullable();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('reseller_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('reseller_id')->constrained('resellers')->cascadeOnDelete();
            $table->string('email', 255)->unique();
            $table->string('full_name', 255)->nullable();
            $table->string('password_hash', 256)->nullable();
            $table->string('role', 32)->default('member');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_users');
        Schema::dropIfExists('reseller_key_assignments');
        Schema::dropIfExists('reseller_key_pools');
        Schema::dropIfExists('reseller_plans');
        Schema::dropIfExists('resellers');
    }
};
