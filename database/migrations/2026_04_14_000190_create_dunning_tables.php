<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dunning_logs');
        Schema::dropIfExists('dunning_configs');

        Schema::create('dunning_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedSmallInteger('step');
            $table->unsignedSmallInteger('days_after_due');
            $table->string('action', 32);
            $table->string('email_template_code', 128)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'step']);
            $table->index(['product_id', 'days_after_due']);
        });

        Schema::create('dunning_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->unsignedSmallInteger('step');
            $table->string('action', 32);
            $table->timestampTz('executed_at')->useCurrent();
            $table->string('result', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'executed_at']);
            $table->index(['result', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_logs');
        Schema::dropIfExists('dunning_configs');
    }
};
