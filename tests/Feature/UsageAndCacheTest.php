<?php

use App\Models\Activation;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Product;
use App\Services\Billing\LicenseCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('admin can store usage records and summary is updated', function (): void {
    $product = Product::query()->create([
        'code' => 'PLUGIN',
        'name' => 'Plugin',
        'description' => null,
        'category' => 'software',
        'status' => 'active',
    ]);

    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'pro',
        'name' => 'Pro',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 3,
        'max_sites' => 3,
        'trial_days' => 0,
    ]);

    $license = LicenseKey::query()->create([
        'plan_id' => $plan->id,
        'license_key' => hash('sha256', 'PRO-KEY-001'),
        'key_display' => 'PRO-KEY-001',
        'status' => 'active',
    ]);

    PlanUsageLimit::query()->create([
        'plan_id' => $plan->id,
        'metric' => 'api_calls',
        'limit_value' => 100,
        'reset_period' => 'monthly',
        'is_soft_limit' => false,
    ]);

    $this->postJson('/v1/admin/usage/records', [
        'license_id' => $license->id,
        'plan_id' => $plan->id,
        'metric' => 'api_calls',
        'quantity' => 40,
    ])->assertSuccessful();

    $this->assertDatabaseHas('usage_records', [
        'license_id' => $license->id,
        'plan_id' => $plan->id,
        'metric' => 'api_calls',
        'quantity' => 40,
    ]);

    $this->assertDatabaseHas('usage_summaries', [
        'license_id' => $license->id,
        'plan_id' => $plan->id,
        'metric' => 'api_calls',
        'total_usage' => 40,
        'limit_value' => 100,
        'usage_percent' => 40,
        'is_over_limit' => false,
    ]);
});

test('license cache invalidates after validation flow', function (): void {
    $product = Product::query()->create([
        'code' => 'PLUGIN2',
        'name' => 'Plugin 2',
        'description' => null,
        'category' => 'software',
        'status' => 'active',
    ]);

    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'pro2',
        'name' => 'Pro 2',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 3,
        'max_sites' => 3,
        'trial_days' => 0,
    ]);

    $entitlementId = (string) \Illuminate\Support\Str::uuid();

    \Illuminate\Support\Facades\DB::table('entitlements')->insert([
        'id' => $entitlementId,
        'plan_id' => $plan->id,
        'customer_id' => null,
        'org_id' => null,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => null,
        'auto_renew' => false,
        'max_activations' => 3,
        'max_sites' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $license = LicenseKey::query()->create([
        'entitlement_id' => $entitlementId,
        'license_key' => hash('sha256', 'PRO-KEY-002'),
        'key_display' => 'PRO-KEY-002',
        'status' => 'issued',
    ]);

    $activation = Activation::query()->create([
        'license_id' => $license->id,
        'activation_code' => 'act_cache_001',
        'product_code' => 'PLUGIN_SEO',
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => 'active',
        'activated_at' => now(),
        'last_validated_at' => now()->subDay(),
    ]);

    $cache = app(LicenseCacheService::class);

    $cache->licenseStatus($license->license_key);
    $cache->policy($license->id);
    $cache->activation($activation->id);

    $this->postJson('/v1/client/licenses/validate', [
        'license_key' => 'PRO-KEY-002',
        'product_code' => 'PLUGIN_SEO',
        'activation_id' => 'act_cache_001',
        'domain' => 'example.com',
    ])->assertSuccessful();

    expect($cache->licenseStatus($license->license_key)['found'])->toBeTrue();
    expect($cache->policy($license->id)['license_id'])->toBe($license->id);
    expect($cache->activation($activation->id)['activation_id'])->toBe($activation->id);
});
