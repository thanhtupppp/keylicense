<?php

use App\Models\AdminUser;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns dashboard summary', function (): void {
    $product = Product::query()->create([
        'code' => 'prod-dash',
        'name' => 'Dashboard Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);
    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-dash',
        'name' => 'Dashboard Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin Dash',
        'email' => 'dash@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'admin',
        'is_active' => true,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
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
    LicenseKey::query()->create([
        'entitlement_id' => $entitlement->id,
        'license_key' => hash('sha256', 'raw-dash-license'),
        'key_display' => 'PROD1-****-****-DASH1',
        'status' => 'active',
        'expires_at' => now()->addMonth(),
    ]);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/dashboard?days=30')
        ->assertSuccessful()
        ->assertJsonPath('data.summary.products', 1);
});
