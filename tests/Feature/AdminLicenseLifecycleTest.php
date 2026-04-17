<?php

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function lifecyclePlan(): Plan
{
    $product = Product::query()->create([
        'code' => 'prod-life',
        'name' => 'Lifecycle Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    return Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-life',
        'name' => 'Lifecycle Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);
}

function lifecycleAdmin(): AdminUser
{
    return AdminUser::query()->create([
        'full_name' => 'Lifecycle Admin',
        'email' => 'lifecycle@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'admin',
        'is_active' => true,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
    ]);
}

function lifecycleLicense(string $status = 'active'): LicenseKey
{
    $plan = lifecyclePlan();
    $customer = Customer::query()->create([
        'email' => 'customer@example.com',
        'full_name' => 'Lifecycle Customer',
        'phone' => null,
        'metadata' => [],
    ]);
    $entitlement = Entitlement::query()->create([
        'plan_id' => $plan->id,
        'customer_id' => $customer->id,
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
        'license_key' => hash('sha256', 'raw-life-license'),
        'key_display' => 'PROD1-****-****-LIFE1',
        'status' => $status,
        'expires_at' => now()->addMonth(),
    ]);
}

it('revoke license transitions status', function (): void {
    $admin = lifecycleAdmin();
    $license = lifecycleLicense('active');

    $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/licenses/{$license->id}/revoke")
        ->assertSuccessful()
        ->assertJsonPath('data.license.status', 'revoked');

    expect(LicenseKey::query()->find($license->id)?->status)->toBe('revoked');
});

it('suspend license transitions status', function (): void {
    $admin = lifecycleAdmin();
    $license = lifecycleLicense('active');

    $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/licenses/{$license->id}/suspend")
        ->assertSuccessful()
        ->assertJsonPath('data.license.status', 'suspended');

    expect(LicenseKey::query()->find($license->id)?->status)->toBe('suspended');
});

it('unsuspend license transitions status', function (): void {
    $admin = lifecycleAdmin();
    $license = lifecycleLicense('suspended');

    $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/licenses/{$license->id}/unsuspend")
        ->assertSuccessful()
        ->assertJsonPath('data.license.status', 'active');

    expect(LicenseKey::query()->find($license->id)?->status)->toBe('active');
});

it('extends license expiry', function (): void {
    $admin = lifecycleAdmin();
    $license = lifecycleLicense('active');
    $before = $license->expires_at;

    $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/licenses/{$license->id}/extend", [
            'days' => 30,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.license.status', 'active');

    expect(LicenseKey::query()->find($license->id)?->expires_at)->not->toEqual($before);
});

it('returns admin dashboard summary', function (): void {
    $admin = lifecycleAdmin();

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'summary',
                'activity',
                'filters',
            ],
        ]);
});
