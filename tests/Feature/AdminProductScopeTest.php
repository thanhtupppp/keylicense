<?php

use App\Models\AdminUser;
use App\Models\Entitlement;
use App\Models\Plan;
use App\Models\Product;
use App\Services\Access\ProductScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows scoped admin to access managed product entitlement', function (): void {
    $product = Product::query()->create([
        'code' => 'prod-scope',
        'name' => 'Scoped Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-scope',
        'name' => 'Scoped Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);

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

    $admin = AdminUser::query()->create([
        'full_name' => 'Scoped Admin',
        'email' => 'scope@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'support',
        'is_active' => true,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
    ]);

    $admin->managedProducts()->attach($product->id);

    expect(app(ProductScopeService::class)->canAccessEntitlement($admin, $entitlement))->toBeTrue();
});

it('blocks scoped admin from unassigned product entitlement', function (): void {
    $product = Product::query()->create([
        'code' => 'prod-scope',
        'name' => 'Scoped Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-scope',
        'name' => 'Scoped Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);

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

    $admin = AdminUser::query()->create([
        'full_name' => 'Scoped Admin',
        'email' => 'scope2@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'support',
        'is_active' => true,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
    ]);

    expect(app(ProductScopeService::class)->canAccessEntitlement($admin, $entitlement))->toBeFalse();
});
