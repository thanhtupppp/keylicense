<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('slug', 64);
            $table->boolean('is_production')->default(false);
            $table->decimal('rate_limit_multiplier', 3, 2)->default(1.00);
            $table->unsignedInteger('heartbeat_interval_hours')->default(12);
            $table->unsignedInteger('grace_period_days')->default(7);
            $table->timestamps();

            $table->unique(['product_id', 'slug']);
        });

        Schema::create('maintenance_windows', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('affects')->default(json_encode(['all']));
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('is_active')->default(false);
            $table->foreignUuid('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
        Schema::dropIfExists('environments');
    }
};
