<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 64);
            $table->enum('subject_type', ['license', 'product', 'admin', 'api_key'])->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->enum('result', ['success', 'failure']);
            $table->enum('severity', ['info', 'warning', 'error'])->default('info');
            $table->timestamp('created_at');

            $table->index('event_type');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_type', 'actor_id']);
            $table->index('ip_address');
            $table->index('created_at');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
