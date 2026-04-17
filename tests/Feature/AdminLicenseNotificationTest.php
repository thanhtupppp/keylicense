<?php

use App\Mail\LicenseIssuedMail;
use App\Mail\LicenseRevokedMail;
use App\Models\Customer;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\AdminAuthFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

function notificationLicensePlan(): Plan
{
    $product = Product::query()->create([
        'code' => 'prod-notify',
        'name' => 'Notify Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    return Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-notify',
        'name' => 'Notify Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 1,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);
}

it('sends license key email after issue', function (): void {
    Mail::fake();
    $admin = AdminAuthFixtures::createAdmin();
    $plan = notificationLicensePlan();
    $customer = Customer::query()->create([
        'email' => 'customer@example.com',
        'full_name' => 'Customer One',
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

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/licenses/issue', [
            'entitlement_id' => $entitlement->getKey(),
            'quantity' => 1,
        ])
        ->assertCreated();

    Mail::assertSent(LicenseIssuedMail::class, 1);
});

it('sends revoke notice email when license is revoked', function (): void {
    Mail::fake();
    $admin = AdminAuthFixtures::createAdmin();
    $plan = notificationLicensePlan();
    $customer = Customer::query()->create([
        'email' => 'customer@example.com',
        'full_name' => 'Customer One',
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
    $license = LicenseKey::query()->create([
        'entitlement_id' => $entitlement->id,
        'license_key' => hash('sha256', 'raw-revoke-license'),
        'key_display' => 'PROD1-****-****-REVOK',
        'status' => 'active',
        'expires_at' => now()->addMonth(),
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson("/api/v1/admin/licenses/{$license->getKey()}/revoke")
        ->assertSuccessful();

    Mail::assertSent(LicenseRevokedMail::class, 1);
});
