<?php

namespace Tests\Support;

use App\Models\Coupon;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Support\Str;

final class BillingFixtures
{
    public static function createSubscription(): Subscription
    {
        $suffix = (string) Str::uuid();

        $product = Product::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'prod-billing-'.$suffix,
            'name' => 'Billing Product',
            'description' => null,
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'code' => 'plan-billing-'.$suffix,
            'name' => 'Billing Plan',
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
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
            'auto_renew' => true,
            'max_activations' => 3,
            'max_sites' => 1,
        ]);

        LicenseKey::query()->create([
            'id' => (string) Str::uuid(),
            'entitlement_id' => $entitlement->id,
            'license_key' => hash('sha256', 'BILLING-LICENSE-KEY-'.$suffix),
            'key_display' => 'BILLING-LICENSE-KEY-'.$suffix,
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        return Subscription::query()->create([
            'id' => (string) Str::uuid(),
            'entitlement_id' => $entitlement->id,
            'status' => 'active',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
            'cancel_at_period_end' => false,
            'metadata' => [],
        ]);
    }

    public static function createCoupon(): Coupon
    {
        return Coupon::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'SAVE10-'.Str::uuid(),
            'name' => 'Save 10',
            'discount_type' => 'fixed',
            'discount_value' => 1000,
            'currency' => 'USD',
            'max_redemptions' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);
    }
}
