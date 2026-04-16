<?php

namespace Tests\Feature\Concerns;

use App\Models\DunningConfig;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;

final class DunningFixtures
{
    public static function createPastDueSubscription(array $overrides = []): Subscription
    {
        $product = Product::query()->create([
            'code' => $overrides['product_code'] ?? 'prod-basic',
            'name' => $overrides['product_name'] ?? 'Basic Product',
            'description' => null,
            'category' => null,
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'product_id' => $product->id,
            'code' => $overrides['plan_code'] ?? 'plan-basic',
            'name' => $overrides['plan_name'] ?? 'Basic Plan',
            'billing_cycle' => 'monthly',
            'price_cents' => 1000,
            'currency' => 'USD',
            'max_activations' => 5,
            'max_sites' => 5,
            'trial_days' => 0,
        ]);

        $entitlement = Entitlement::query()->create([
            'plan_id' => $plan->id,
            'customer_id' => (string) str()->uuid(),
            'org_id' => (string) str()->uuid(),
            'status' => 'active',
            'starts_at' => now()->subDays(40),
            'expires_at' => now()->addDays(20),
            'auto_renew' => true,
            'max_activations' => 5,
            'max_sites' => 5,
        ]);

        LicenseKey::query()->create([
            'entitlement_id' => $entitlement->id,
            'license_key' => (string) str()->uuid(),
            'key_display' => 'AAAA-BBBB-CCCC-DDDD',
            'status' => 'suspended',
            'expires_at' => now()->addDays(20),
        ]);

        $subscription = Subscription::query()->create([
            'entitlement_id' => $entitlement->id,
            'customer_id' => $entitlement->customer_id,
            'org_id' => $entitlement->org_id,
            'external_id' => 'sub_123',
            'source' => 'stripe',
            'status' => 'past_due',
            'current_period_start' => now()->subDays(10),
            'current_period_end' => now()->addDays(20),
            'cancel_at_period_end' => false,
            'metadata' => [],
        ]);

        $subscription->forceFill([
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ])->saveQuietly();

        return $subscription;
    }

    public static function createDunningConfig(int $step, string $action = DunningConfig::ACTION_EMAIL, ?string $productId = null): DunningConfig
    {
        return DunningConfig::query()->create([
            'product_id' => $productId,
            'step' => $step,
            'days_after_due' => $step,
            'action' => $action,
            'email_template_code' => "dunning-step-{$step}",
        ]);
    }

    public static function seedPastDueSubscription(array $overrides = []): Subscription
    {
        return self::createPastDueSubscription($overrides);
    }

    public static function seedDunningConfig(int $step, string $action = DunningConfig::ACTION_EMAIL, ?string $productId = null): DunningConfig
    {
        return self::createDunningConfig($step, $action, $productId);
    }
}
