<?php

use App\Models\Activation;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function reportAdmin(): AdminUser
{
    return AdminUser::query()->create([
        'full_name' => 'Report Admin',
        'email' => 'report@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'admin',
        'is_active' => true,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
    ]);
}

function reportLicense(string $status = 'active', ?string $expiresAt = null): LicenseKey
{
    $product = Product::query()->create([
        'code' => 'prod-report',
        'name' => 'Report Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);
    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-report',
        'name' => 'Report Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);
    $customer = Customer::query()->create([
        'email' => 'report@example.com',
        'full_name' => 'Report Customer',
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
        'license_key' => hash('sha256', 'raw-report-license'.uniqid('', true)),
        'key_display' => 'PROD1-****-****-RPT01',
        'status' => $status,
        'expires_at' => $expiresAt ? now()->addDays(5) : now()->addDays(5),
    ]);
}

it('returns expiring report with filters', function (): void {
    $admin = reportAdmin();
    reportLicense('active');

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/reports/expiring?days=30&status=active')
        ->assertSuccessful()
        ->assertJsonPath('data.report.count', 1);
});

it('returns activation report with filters', function (): void {
    $admin = reportAdmin();
    $license = reportLicense('active');

    Activation::query()->create([
        'activation_code' => 'act_report_001',
        'license_id' => $license->id,
        'product_code' => 'prod-report',
        'domain' => 'report.example.com',
        'environment' => 'production',
        'status' => 'active',
        'activated_at' => now(),
        'last_validated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/reports/activations?status=active')
        ->assertSuccessful()
        ->assertJsonPath('data.report.count', 1);
});

it('exports report as csv metadata', function (): void {
    $admin = reportAdmin();

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/reports/export?type=expiring&format=csv')
        ->assertSuccessful()
        ->assertJsonPath('data.export.format', 'csv');
});
