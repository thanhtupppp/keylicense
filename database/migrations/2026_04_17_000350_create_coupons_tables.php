<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 128)->unique();
            $table->string('name');
            $table->string('discount_type', 32);
            $table->unsignedInteger('discount_value');
            $table->string('currency', 8)->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignUuid('license_id')->nullable()->constrained('license_keys')->nullOnDelete();
            $table->unsignedInteger('discount_amount')->default(0);
            $table->timestampTz('redeemed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
