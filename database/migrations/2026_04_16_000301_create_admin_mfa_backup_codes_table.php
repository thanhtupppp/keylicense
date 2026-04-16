<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_mfa_backup_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->string('code_hash');
            $table->timestampTz('used_at')->nullable();
            $table->timestamps();

            $table->index(['admin_user_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_mfa_backup_codes');
    }
};
