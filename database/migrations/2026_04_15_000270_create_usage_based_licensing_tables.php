<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_usage_limits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('metric', 64);
            $table->unsignedBigInteger('limit_value');
            $table->string('reset_period', 32)->default('monthly');
            $table->boolean('is_soft_limit')->default(false);
            $table->timestamps();

            $table->unique(['plan_id', 'metric']);
        });

        Schema::create('usage_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_id')->constrained('license_keys')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('metric', 64);
            $table->unsignedBigInteger('quantity');
            $table->json('dimensions')->nullable();
            $table->timestampTz('recorded_at');
            $table->timestamps();

            $table->index(['license_id', 'metric', 'recorded_at']);
            $table->index(['plan_id', 'metric', 'recorded_at']);
        });

        Schema::create('usage_summaries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_id')->constrained('license_keys')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('metric', 64);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('total_usage')->default(0);
            $table->unsignedBigInteger('limit_value')->nullable();
            $table->unsignedSmallInteger('usage_percent')->nullable();
            $table->boolean('is_over_limit')->default(false);
            $table->timestamps();

            $table->unique(['license_id', 'metric', 'period_start', 'period_end'], 'usage_summary_period_unique');
            $table->index(['plan_id', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_summaries');
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('plan_usage_limits');
    }
};
