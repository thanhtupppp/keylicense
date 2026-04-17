<?php

use App\Models\Entitlement;
use App\Models\Plan;
use App\Models\Product;
use App\Services\Reporting\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\LicenseTransferFixtures;
use Tests\Support\PortalFixtures;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createPortalPlan(): Plan
{
    $product = Product::query()->create([
        'id' => (string) Str::uuid(),
        'code' => 'portal-prod-'.Str::uuid(),
        'name' => 'Portal Product',
        'description' => null,
        'is_active' => true,
    ]);

    return Plan::query()->create([
        'id' => (string) Str::uuid(),
        'product_id' => $product->id,
        'code' => 'portal-plan-'.Str::uuid(),
        'name' => 'Portal Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1200,
        'currency' => 'USD',
        'max_activations' => 2,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);
}

test('admin portal dashboard shows analytics metrics', function (): void {
    $admin = PortalFixtures::createAdmin();

    $response = $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password123',
        'remember' => true,
    ]);

    $response->assertRedirect('/admin/dashboard');

    $this->get('/admin/dashboard')->assertSuccessful();
});

test('analytics service returns dashboard counts', function (): void {
    $plan = createPortalPlan();
    Entitlement::query()->create([
        'id' => (string) Str::uuid(),
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(10),
        'auto_renew' => true,
        'max_activations' => 2,
        'max_sites' => 1,
    ]);

    $metrics = app(AnalyticsService::class)->dashboard();

    expect($metrics)->toHaveKeys(['active_licenses', 'active_activations', 'churned_subscriptions', 'expiring_entitlements']);
});

test('license transfer auto revokes activations', function (): void {
    $license = LicenseTransferFixtures::createTransferableLicense();
    $from = LicenseTransferFixtures::createCustomer('from@example.com');
    $to = LicenseTransferFixtures::createCustomer('to@example.com');
    $admin = PortalFixtures::createAdmin();
    $headers = PortalFixtures::sessionHeaders($admin);

    $response = $this->withHeaders($headers)->postJson('/api/v1/admin/license-transfers', [
        'license_key_id' => $license->id,
        'from_customer_id' => $from->id,
        'to_customer_id' => $to->id,
        'reason' => 'customer_request',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.auto_revoke_activations', true);
});
