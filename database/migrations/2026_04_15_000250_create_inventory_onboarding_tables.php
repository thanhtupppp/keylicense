<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestampTz('email_verified_at')->nullable();
            }
            if (! Schema::hasColumn('customers', 'verification_token')) {
                $table->string('verification_token', 128)->nullable()->index();
            }
            if (! Schema::hasColumn('customers', 'verification_expires_at')) {
                $table->timestampTz('verification_expires_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'verification_expires_at')) {
                $table->dropColumn('verification_expires_at');
            }
            if (Schema::hasColumn('customers', 'verification_token')) {
                $table->dropColumn('verification_token');
            }
            if (Schema::hasColumn('customers', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
        });
    }
};
