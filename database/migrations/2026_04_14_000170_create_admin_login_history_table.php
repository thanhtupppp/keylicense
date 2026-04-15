<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_login_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('admin_id')->constrained('admin_users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('location', 128)->nullable();
            $table->boolean('success');
            $table->string('failure_reason', 64)->nullable();
            $table->timestampTz('occurred_at')->useCurrent();

            $table->index(['admin_id', 'occurred_at']);
            $table->index(['success', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_login_history');
    }
};
