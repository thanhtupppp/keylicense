<?php

use App\Models\Customer;
use App\Models\DataRequest;
use App\Models\DataRetentionPolicy;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable('data_requests')) {
        Schema::create('data_requests', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->nullable();
            $table->string('request_type', 32);
            $table->string('status', 32)->default('pending');
            $table->timestampTz('requested_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->text('export_url')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('data_retention_policies')) {
        Schema::create('data_retention_policies', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('data_type', 64)->unique();
            $table->unsignedInteger('retention_days');
            $table->boolean('anonymize')->default(false);
            $table->text('description')->nullable();
            $table->timestampTz('updated_at')->nullable();
        });
    }

    // Middleware is disabled per test suite to keep feature flows focused.
    $this->withoutMiddleware();
});

test('customer can request data erasure and anonymize profile', function (): void {
    $customer = Customer::query()->create([
        'email' => 'gdpr@example.com',
        'full_name' => 'GDPR User',
        'phone' => '123456789',
        'metadata' => ['legacy' => true],
    ]);

    $this->postJson('/api/v1/customer/data-requests', [
        'request_type' => 'erasure',
        'customer_id' => $customer->id,
        'notes' => 'please delete my data',
    ])->assertCreated();

    $this->assertDatabaseHas('data_requests', [
        'customer_id' => $customer->id,
        'request_type' => 'erasure',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'email' => 'deleted_'.$customer->id.'@anonymized.invalid',
        'full_name' => '[DELETED]',
        'phone' => null,
    ]);
});

test('retention policy can be resolved by data type', function (): void {
    DataRetentionPolicy::query()->create([
        'data_type' => 'dunning_logs',
        'retention_days' => 90,
        'anonymize' => false,
        'description' => 'Keep dunning logs for 90 days.',
        'updated_at' => now(),
    ]);

    $policy = app(\App\Services\Privacy\DataRetentionService::class)->policyFor('dunning_logs');

    expect($policy)->not->toBeNull()
        ->and($policy?->retention_days)->toBe(90)
        ->and($policy?->anonymize)->toBeFalse();
});
