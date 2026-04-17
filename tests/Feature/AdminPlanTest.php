<?php

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AdminAuthFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('lists plans', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $product = Product::query()->create([
        'code' => 'prod-10',
        'name' => 'Product Ten',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-1',
        'name' => 'Plan One',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => null,
        'max_sites' => null,
        'trial_days' => 0,
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->getJson('/api/v1/admin/plans')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.plans');
});

it('creates a plan', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $product = Product::query()->create([
        'code' => 'prod-11',
        'name' => 'Product Eleven',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/plans', [
            'product_id' => $product->id,
            'code' => 'plan-2',
            'name' => 'Plan Two',
            'billing_cycle' => 'annual',
            'price_cents' => 2500,
        ])
        ->assertCreated()
        ->assertJsonPath('data.plan.code', 'plan-2');
});

it('updates a plan', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $product = Product::query()->create([
        'code' => 'prod-12',
        'name' => 'Product Twelve',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);
    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-3',
        'name' => 'Plan Three',
        'billing_cycle' => 'monthly',
        'price_cents' => 1500,
        'currency' => 'USD',
        'max_activations' => null,
        'max_sites' => null,
        'trial_days' => 0,
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->patchJson('/api/v1/admin/plans/'.$plan->id, [
            'name' => 'Plan Three Updated',
            'price_cents' => 1700,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.plan.name', 'Plan Three Updated');
});

it('deletes a plan', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $product = Product::query()->create([
        'code' => 'prod-13',
        'name' => 'Product Thirteen',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);
    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-4',
        'name' => 'Plan Four',
        'billing_cycle' => 'monthly',
        'price_cents' => 1800,
        'currency' => 'USD',
        'max_activations' => null,
        'max_sites' => null,
        'trial_days' => 0,
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->deleteJson('/api/v1/admin/plans/'.$plan->id)
        ->assertSuccessful()
        ->assertJsonPath('data.deleted', true);

    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});
