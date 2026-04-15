<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_pricing', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('currency', 8);
            $table->unsignedInteger('price_cents');
            $table->boolean('is_default')->default(false);
            $table->timestampTz('valid_from')->useCurrent();
            $table->timestampTz('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'currency']);
            $table->index(['plan_id', 'is_default']);
        });

        Schema::create('data_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->nullable()->index();
            $table->string('request_type', 32);
            $table->string('status', 32)->default('pending');
            $table->timestampTz('requested_at')->useCurrent();
            $table->timestampTz('completed_at')->nullable();
            $table->text('export_url')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('processed_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('data_retention_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('data_type', 64)->unique();
            $table->unsignedInteger('retention_days');
            $table->boolean('anonymize')->default(false);
            $table->text('description')->nullable();
            $table->timestampTz('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_retention_policies');
        Schema::dropIfExists('data_requests');
        Schema::dropIfExists('plan_pricing');
    }
};
