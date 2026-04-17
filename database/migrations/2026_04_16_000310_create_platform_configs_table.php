<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_configs')) {
            return;
        }

        Schema::create('platform_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key', 128)->unique();
            $table->text('value');
            $table->string('value_type', 32)->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->foreignUuid('updated_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestampTz('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_configs');
    }
};
