<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->boolean('mfa_enabled')->default(false)->after('is_active');
            $table->string('mfa_secret')->nullable()->after('mfa_enabled');
            $table->timestampTz('mfa_enabled_at')->nullable()->after('mfa_secret');
            $table->unsignedSmallInteger('mfa_failed_attempts')->default(0)->after('mfa_enabled_at');
            $table->timestampTz('mfa_locked_until')->nullable()->after('mfa_failed_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->dropColumn([
                'mfa_enabled',
                'mfa_secret',
                'mfa_enabled_at',
                'mfa_failed_attempts',
                'mfa_locked_until',
            ]);
        });
    }
};
