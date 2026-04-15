<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('device_key', 191)->index();
            $table->timestampTz('last_activity_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['admin_user_id', 'revoked_at']);
            $table->index(['admin_user_id', 'device_key', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_tokens');
    }
};
