<?php

use App\Models\Environment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Middleware is disabled per test suite to keep environment flows isolated.
    $this->withoutMiddleware();
});

test('client environment endpoint returns environment config for product', function (): void {
    $product = Product::query()->create([
        'code' => 'prod-test',
        'name' => 'Test Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    Environment::query()->create([
        'product_id' => $product->id,
        'name' => 'Production',
        'slug' => 'production',
        'is_production' => true,
        'rate_limit_multiplier' => 1.0,
        'heartbeat_interval_hours' => 12,
        'grace_period_days' => 7,
    ]);

    $this->withHeader('X-API-Key', 'client-test-key')
        ->getJson('/api/v1/client/environment?environment=production')
        ->assertSuccessful()
        ->assertJsonPath('data.environment.slug', 'production')
        ->assertJsonPath('data.environment.is_production', true);
});

test('environment endpoint returns not found when config is missing', function (): void {
    $this->withHeader('X-API-Key', 'client-test-key')
        ->getJson('/api/v1/client/environment?environment=staging')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'not_found');
});
