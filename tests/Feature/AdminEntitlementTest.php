<?php

use App\Models\Entitlement;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AdminAuthFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

function entitlementPlan(): Plan
{
    $product = Product::query()->create([
        'code' => 'prod-ent',
        'name' => 'Entitlement Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    return Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-ent',
        'name' => 'Entitlement Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);
}

it('lists entitlements with filters', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $plan = entitlementPlan();

    Entitlement::query()->create([
        'plan_id' => $plan->id,
        'customer_id' => fake()->uuid(),
        'org_id' => null,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
        'auto_renew' => false,
        'max_activations' => 1,
        'max_sites' => 1,
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->getJson('/api/v1/admin/entitlements?status=active')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.entitlements.data');
});

it('creates an entitlement', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $plan = entitlementPlan();

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/entitlements', [
            'plan_id' => $plan->id,
            'customer_id' => fake()->uuid(),
            'starts_at' => now()->toISOString(),
            'auto_renew' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.entitlement.plan_id', $plan->id);
});

it('shows an entitlement', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $plan = entitlementPlan();
    $entitlement = Entitlement::query()->create([
        'plan_id' => $plan->id,
        'customer_id' => fake()->uuid(),
        'org_id' => null,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
        'auto_renew' => false,
        'max_activations' => 1,
        'max_sites' => 1,
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->getJson('/api/v1/admin/entitlements/'.$entitlement->id)
        ->assertSuccessful()
        ->assertJsonPath('data.entitlement.id', $entitlement->id);
});
