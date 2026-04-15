<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key', 128)->unique();
            $table->text('value');
            $table->string('value_type', 32)->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->uuid('updated_by')->nullable()->index();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('is_sensitive');
            $table->index('value_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_configs');
    }
};
