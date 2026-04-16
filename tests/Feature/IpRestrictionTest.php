<?php

use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('admin can manage ip blocklist and allowlist', function (): void {
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

    $entitlement = Entitlement::query()->create([
        'plan_id' => $plan->id,
        'starts_at' => now(),
        'status' => 'active',
    ]);

    $license = LicenseKey::query()->create([
        'entitlement_id' => $entitlement->id,
        'license_key' => hash('sha256', 'LICENSE-001'),
        'key_display' => 'LICENSE-001',
        'status' => 'active',
    ]);

    $this->postJson("/v1/admin/licenses/{$license->id}/ip-allowlist", [
        'cidr' => '10.0.0.1/32',
        'label' => 'office',
    ])->assertSuccessful();

    $this->postJson('/v1/admin/ip-blocklist', [
        'cidr' => '203.0.113.1/32',
        'reason' => 'fraud',
    ])->assertSuccessful();

    $this->putJson("/v1/admin/plans/{$plan->id}/geo-restrictions", [
        'restriction_type' => 'blocklist',
        'country_codes' => ['VN'],
    ])->assertSuccessful();

    $this->assertDatabaseHas('license_ip_allowlists', ['license_key_id' => $license->id, 'cidr' => '10.0.0.1/32']);
    $this->assertDatabaseHas('ip_blocklist', ['cidr' => '203.0.113.1/32']);
    $this->assertDatabaseHas('plan_geo_restrictions', ['plan_id' => $plan->id, 'restriction_type' => 'blocklist']);
});
