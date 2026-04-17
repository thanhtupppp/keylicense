<?php

use App\Models\Environment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns environment policy and rate limit multiplier', function (): void {
    $product = Product::query()->create([
        'code' => 'prod-env',
        'name' => 'Env Product',
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

    $this->getJson('/api/v1/client/environment?environment=production')
        ->assertSuccessful()
        ->assertJsonPath('data.environment.slug', 'production')
        ->assertJsonPath('data.environment.rate_limit_multiplier', 1.0);
});
