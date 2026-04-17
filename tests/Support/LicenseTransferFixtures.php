<?php

namespace Tests\Support;

use App\Models\Customer;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Support\Str;

final class LicenseTransferFixtures
{
    public static function createTransferableLicense(): LicenseKey
    {
        $product = Product::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'transfer-prod-'.Str::uuid(),
            'name' => 'Transfer Product',
            'description' => null,
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'code' => 'transfer-plan-'.Str::uuid(),
            'name' => 'Transfer Plan',
            'billing_cycle' => 'monthly',
            'price_cents' => 1200,
            'currency' => 'USD',
            'max_activations' => 2,
            'max_sites' => 1,
            'trial_days' => 0,
        ]);

        $entitlement = Entitlement::query()->create([
            'id' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonth(),
            'auto_renew' => true,
            'max_activations' => 2,
            'max_sites' => 1,
        ]);

        return LicenseKey::query()->create([
            'id' => (string) Str::uuid(),
            'entitlement_id' => $entitlement->id,
            'license_key' => hash('sha256', 'TRANSFER-LICENSE-'.Str::uuid()),
            'key_display' => 'TRANSFER-LICENSE',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);
    }

    public static function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'email' => $email,
            'full_name' => 'Customer',
            'phone' => null,
            'metadata' => [],
        ]);
    }
}
