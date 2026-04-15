<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_token_audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->uuid('admin_token_id')->nullable()->index();
            $table->string('event', 64)->index(); // login|kick|rotate|revoke|revoke_all_except_current|expired
            $table->string('actor_type', 32)->default('system'); // system|admin
            $table->uuid('actor_admin_user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_token_audit_logs');
    }
};
