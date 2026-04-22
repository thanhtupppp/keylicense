<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->char('key_hash', 64)->unique();
            $table->char('key_last4', 4);
            $table->enum('license_model', ['per-device', 'per-user', 'floating']);
            $table->enum('status', ['inactive', 'active', 'expired', 'revoked', 'suspended'])->default('inactive');
            $table->unsignedSmallInteger('max_seats')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index('status');
            $table->index('expiry_date');
            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
