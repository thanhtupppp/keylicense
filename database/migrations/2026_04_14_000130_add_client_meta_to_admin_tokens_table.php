<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_tokens', function (Blueprint $table): void {
            $table->string('last_ip', 45)->nullable()->after('device_key');
            $table->text('last_user_agent')->nullable()->after('last_ip');
        });
    }

    public function down(): void
    {
        Schema::table('admin_tokens', function (Blueprint $table): void {
            $table->dropColumn(['last_ip', 'last_user_agent']);
        });
    }
};
