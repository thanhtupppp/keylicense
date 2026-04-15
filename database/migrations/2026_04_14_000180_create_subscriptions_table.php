<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('entitlement_id')->constrained('entitlements')->cascadeOnDelete();
            $table->uuid('customer_id')->nullable()->index();
            $table->uuid('org_id')->nullable()->index();
            $table->string('external_id', 128)->nullable()->index();
            $table->string('source', 64)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestampTz('current_period_start')->nullable();
            $table->timestampTz('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['entitlement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
