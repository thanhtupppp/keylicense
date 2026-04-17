<?php

use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function updateCheckPlan(): Plan
{
    $product = Product::query()->create([
        'code' => 'prod-update',
        'name' => 'Update Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    ProductVersion::query()->create([
        'product_id' => $product->id,
        'version' => '1.1.0',
        'build_number' => '110',
        'release_notes' => 'Latest improvements',
        'is_latest' => true,
        'is_required' => false,
    ]);

    ProductVersion::query()->create([
        'product_id' => $product->id,
        'version' => '1.0.5',
        'build_number' => '105',
        'release_notes' => 'Security patch',
        'is_latest' => false,
        'is_required' => true,
    ]);

    return Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-update',
        'name' => 'Update Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);
}

it('checks update availability for client license', function (): void {
    $plan = updateCheckPlan();

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
        'license_key' => hash('sha256', 'raw-update-license'),
        'key_display' => 'PROD1-****-****-UPDT1',
        'status' => 'active',
        'expires_at' => now()->addMonth(),
    ]);

    $this->postJson('/api/v1/client/updates/check', [
        'license_key' => 'raw-update-license',
        'product_code' => 'prod-update',
        'current_version' => '1.0.0',
    ], [
        'X-API-Key' => 'test-client-key',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.update_available', true)
        ->assertJsonPath('data.required_update', true)
        ->assertJsonPath('data.latest_version', '1.1.0');
});
