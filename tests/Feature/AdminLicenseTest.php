<?php

use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AdminAuthFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

function licensePlan(): Plan
{
    $product = Product::query()->create([
        'code' => 'prod-license',
        'name' => 'License Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    return Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-license',
        'name' => 'License Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);
}

function issuedLicense(): LicenseKey
{
    $plan = licensePlan();
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

    return LicenseKey::query()->create([
        'entitlement_id' => $entitlement->id,
        'license_key' => hash('sha256', 'raw-license-key'),
        'key_display' => 'PROD1-****-****-KEY01',
        'status' => 'issued',
        'expires_at' => now()->addMonth(),
    ]);
}

it('issues a license from entitlement', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $plan = licensePlan();
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
        ->postJson('/api/v1/admin/licenses/issue', [
            'entitlement_id' => $entitlement->id,
            'quantity' => 1,
        ])
        ->assertCreated()
        ->assertJsonCount(1, 'data.licenses')
        ->assertJsonStructure([
            'data' => [
                'licenses' => [
                    ['id', 'license_key', 'key_display', 'status', 'expires_at'],
                ],
            ],
        ]);
});

it('revokes a license', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $license = issuedLicense();

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/licenses/'.$license->id.'/revoke')
        ->assertSuccessful()
        ->assertJsonPath('data.license.status', 'revoked');

    $this->assertDatabaseHas('license_keys', [
        'id' => $license->id,
        'status' => 'revoked',
    ]);
});

it('suspends and unsuspends a license', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $license = issuedLicense();

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/licenses/'.$license->id.'/suspend')
        ->assertSuccessful()
        ->assertJsonPath('data.license.status', 'suspended');

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/licenses/'.$license->id.'/unsuspend')
        ->assertSuccessful()
        ->assertJsonPath('data.license.status', 'active');
});

it('extends a license expiry', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $license = issuedLicense();

    $expected = $license->expires_at->copy()->addDays(30)->toISOString();

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/licenses/'.$license->id.'/extend', [
            'days' => 30,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.license.expires_at', $expected);
});

it('shows activation history', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $license = issuedLicense();
    $activation = App\Models\Activation::query()->create([
        'activation_code' => 'act_test_0001',
        'license_id' => $license->id,
        'product_code' => 'prod-license',
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => 'active',
        'activated_at' => now(),
        'last_validated_at' => now(),
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->getJson('/api/v1/admin/licenses/'.$license->id.'/history')
        ->assertSuccessful()
        ->assertJsonPath('data.activation_count', 1)
        ->assertJsonPath('data.activations.0.activation_code', $activation->activation_code);
});

it('deactivates a client activation', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $license = issuedLicense();
    $activation = App\Models\Activation::query()->create([
        'activation_code' => 'act_test_0001',
        'license_id' => $license->id,
        'product_code' => 'prod-license',
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => 'active',
        'activated_at' => now(),
        'last_validated_at' => now(),
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/client/licenses/deactivate', [
            'license_key' => 'raw-license-key',
            'activation_id' => $activation->activation_code,
            'domain' => 'example.com',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'deactivated')
        ->assertJsonPath('data.activation_id', $activation->activation_code);

    $this->assertDatabaseHas('activations', [
        'id' => $activation->id,
        'status' => 'deactivated',
    ]);
});
