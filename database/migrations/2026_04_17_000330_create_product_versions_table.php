<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('version', 64);
            $table->string('build_number', 64)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_latest')->default(false);
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'version']);
            $table->index(['product_id', 'is_latest']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_versions');
    }
};
