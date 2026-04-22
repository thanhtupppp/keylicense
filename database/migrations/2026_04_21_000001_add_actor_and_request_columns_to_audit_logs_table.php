<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_type')->nullable()->after('subject_id');
            $table->unsignedBigInteger('actor_id')->nullable()->after('actor_type');
            $table->string('actor_name')->nullable()->after('actor_id');
            $table->text('user_agent')->nullable()->after('ip_address');

            $table->index(['actor_type', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['actor_type', 'actor_id']);
            $table->dropColumn(['actor_type', 'actor_id', 'actor_name', 'user_agent']);
        });
    }
};
