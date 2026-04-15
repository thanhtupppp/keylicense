<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->nullable()->index();
            $table->foreignUuid('entitlement_id')->nullable()->constrained('entitlements')->nullOnDelete();
            $table->string('external_id', 128)->nullable();
            $table->string('refund_type', 32);
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 8)->default('USD');
            $table->string('reason', 64)->nullable();
            $table->string('status', 32)->default('pending');
            $table->boolean('auto_revoke')->default(true);
            $table->string('initiated_by', 32)->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->nullable()->index();
            $table->uuid('org_id')->nullable()->index();
            $table->boolean('is_default')->default(false);
            $table->string('full_name')->nullable();
            $table->string('company')->nullable();
            $table->text('address_line1')->nullable();
            $table->text('address_line2')->nullable();
            $table->string('city', 128)->nullable();
            $table->string('state', 128)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('country', 8)->nullable();
            $table->string('tax_id', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->nullable()->index();
            $table->uuid('customer_id')->nullable()->index();
            $table->uuid('org_id')->nullable()->index();
            $table->string('invoice_number', 64)->unique();
            $table->string('status', 32)->default('issued');
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->string('currency', 8)->default('USD');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->json('billing_address')->nullable();
            $table->text('pdf_url')->nullable();
            $table->timestampTz('issued_at')->useCurrent();
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->text('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('total_cents');
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->index();
            $table->string('notification_code', 128);
            $table->string('channel', 32);
            $table->boolean('enabled')->default(true);
            $table->string('unsubscribe_token', 128)->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'notification_code', 'channel']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('billing_addresses');
        Schema::dropIfExists('refunds');
    }
};
