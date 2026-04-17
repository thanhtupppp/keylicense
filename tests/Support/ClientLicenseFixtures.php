<?php

namespace Tests\Support;

use App\Models\ApiKey;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Support\Str;

class ClientLicenseFixtures
{
    public static function seedApiKey(): void
    {
        ApiKey::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Client Test Key',
            'api_key' => hash('sha256', 'test-client-key'),
            'scope' => 'client',
            'is_active' => true,
        ]);
    }

    public static function createLicense(string $status = 'active'): LicenseKey
    {
        $product = Product::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'prod-1',
            'name' => 'Product 1',
            'description' => null,
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'code' => 'plan-1',
            'name' => 'Plan 1',
            'billing_cycle' => 'monthly',
            'price_cents' => 1000,
            'currency' => 'USD',
            'max_activations' => 3,
            'max_sites' => 1,
            'trial_days' => 0,
        ]);

        $entitlement = Entitlement::query()->create([
            'id' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'auto_renew' => false,
            'max_activations' => 3,
            'max_sites' => 1,
        ]);

        return LicenseKey::query()->create([
            'id' => (string) Str::uuid(),
            'entitlement_id' => $entitlement->id,
            'license_key' => hash('sha256', 'TEST-LICENSE-KEY'),
            'key_display' => 'TEST-LICENSE-KEY',
            'status' => $status,
            'expires_at' => now()->addMonth(),
        ]);
    }
}
