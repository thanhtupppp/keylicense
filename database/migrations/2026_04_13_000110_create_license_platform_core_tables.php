<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 64)->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('billing_cycle', 32);
            $table->unsignedInteger('price_cents');
            $table->string('currency', 8)->default('USD');
            $table->unsignedInteger('max_activations')->nullable();
            $table->unsignedInteger('max_sites')->nullable();
            $table->unsignedInteger('trial_days')->default(0);
            $table->timestamps();
        });

        Schema::create('entitlements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->uuid('customer_id')->nullable()->index();
            $table->uuid('org_id')->nullable()->index();
            $table->string('status', 32)->default('active');
            $table->timestampTz('starts_at');
            $table->timestampTz('expires_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->unsignedInteger('max_activations')->nullable();
            $table->unsignedInteger('max_sites')->nullable();
            $table->timestamps();
        });

        Schema::create('license_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('entitlement_id')->constrained('entitlements')->cascadeOnDelete();
            $table->string('license_key', 64)->unique();
            $table->string('key_display', 64);
            $table->string('status', 32)->default('issued');
            $table->timestampTz('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('activation_code', 64)->unique();
            $table->foreignUuid('license_id')->constrained('license_keys')->cascadeOnDelete();
            $table->string('product_code', 64);
            $table->string('domain', 255);
            $table->string('environment', 32)->default('production');
            $table->string('status', 32)->default('active');
            $table->timestampTz('activated_at');
            $table->timestampTz('last_validated_at')->nullable();
            $table->string('offline_challenge', 128)->nullable();
            $table->timestampTz('offline_challenge_expires_at')->nullable();
            $table->timestampTz('offline_challenge_used_at')->nullable();
            $table->timestamps();

            $table->index(['license_id', 'domain']);
        });

        Schema::create('api_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('api_key', 64)->unique();
            $table->string('scope', 32)->default('client');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('activations');
        Schema::dropIfExists('license_keys');
        Schema::dropIfExists('entitlements');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('products');
    }
};
