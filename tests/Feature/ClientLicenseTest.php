<?php

use App\Models\Activation;
use App\Models\ApiKey;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    ApiKey::query()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Client Test Key',
        'api_key' => hash('sha256', 'test-client-key'),
        'scope' => 'client',
        'is_active' => true,
    ]);
});

function clientLicensePlan(): array
{
    $product = Product::query()->create([
        'code' => 'prod-client',
        'name' => 'Client Product',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    $plan = Plan::query()->create([
        'product_id' => $product->id,
        'code' => 'plan-client',
        'name' => 'Client Plan',
        'billing_cycle' => 'monthly',
        'price_cents' => 1000,
        'currency' => 'USD',
        'max_activations' => 5,
        'max_sites' => 1,
        'trial_days' => 0,
    ]);

    $entitlement = Entitlement::query()->create([
        'plan_id' => $plan->id,
        'customer_id' => fake()->uuid(),
        'org_id' => null,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
        'auto_renew' => false,
        'max_activations' => 5,
        'max_sites' => 1,
    ]);

    $license = LicenseKey::query()->create([
        'entitlement_id' => $entitlement->id,
        'license_key' => hash('sha256', 'raw-client-license'),
        'key_display' => 'PROD1-****-****-CLNT1',
        'status' => 'active',
        'expires_at' => now()->addMonth(),
    ]);

    return [$product, $plan, $entitlement, $license];
}

it('activates a client license', function (): void {
    [$product, , , $license] = clientLicensePlan();

    $this->postJson('/api/v1/client/licenses/activate', [
        'license_key' => 'raw-client-license',
        'product_code' => $product->code,
        'domain' => 'example.com',
        'environment' => 'production',
    ], [
        'X-API-Key' => 'test-client-key',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.payload.license_id', $license->id);

    $this->assertDatabaseHas('activations', [
        'license_id' => $license->id,
        'domain' => 'example.com',
        'status' => 'active',
    ]);
});

it('validates an active activation', function (): void {
    [$product, , , $license] = clientLicensePlan();
    $activation = Activation::query()->create([
        'activation_code' => 'act_client_0001',
        'license_id' => $license->id,
        'product_code' => $product->code,
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => 'active',
        'activated_at' => now(),
        'last_validated_at' => now()->subHour(),
    ]);

    $this->postJson('/api/v1/client/licenses/validate', [
        'license_key' => 'raw-client-license',
        'product_code' => $product->code,
        'activation_id' => $activation->activation_code,
        'domain' => 'example.com',
        'environment' => 'production',
    ], [
        'X-API-Key' => 'test-client-key',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'valid')
        ->assertJsonPath('data.license.activation_id', $activation->activation_code);
});

it('deactivates a client activation', function (): void {
    [$product, , , $license] = clientLicensePlan();
    $activation = Activation::query()->create([
        'activation_code' => 'act_client_0002',
        'license_id' => $license->id,
        'product_code' => $product->code,
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => 'active',
        'activated_at' => now(),
        'last_validated_at' => now(),
    ]);

    $this->postJson('/api/v1/client/licenses/deactivate', [
        'license_key' => 'raw-client-license',
        'activation_id' => $activation->activation_code,
    ], [
        'X-API-Key' => 'test-client-key',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'deactivated')
        ->assertJsonPath('data.activation_id', $activation->activation_code);

    $this->assertDatabaseHas('activations', [
        'id' => $activation->id,
        'status' => 'deactivated',
    ]);
});

it('requests and confirms offline challenge', function (): void {
    [$product, , , $license] = clientLicensePlan();

    $challengeResponse = $this->postJson('/api/v1/client/licenses/offline/request', [
        'license_key' => 'raw-client-license',
        'product_code' => $product->code,
        'domain' => 'example.com',
        'environment' => 'production',
    ], [
        'X-API-Key' => 'test-client-key',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.payload.status', 'challenge_issued');

    $activationId = $challengeResponse->json('data.challenge_id');

    $this->postJson('/api/v1/client/licenses/offline/confirm', [
        'license_key' => 'raw-client-license',
        'activation_id' => $activationId,
        'challenge' => 'signed-offline-proof',
    ], [
        'X-API-Key' => 'test-client-key',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.payload.status', 'challenge_confirmed');

    $this->assertDatabaseHas('activations', [
        'license_id' => $license->id,
        'activation_code' => $activationId,
        'status' => 'active',
    ]);
});

it('rejects validation for deactivated activation', function (): void {
    [$product, , , $license] = clientLicensePlan();
    $activation = Activation::query()->create([
        'activation_code' => 'act_client_0003',
        'license_id' => $license->id,
        'product_code' => $product->code,
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => 'deactivated',
        'activated_at' => now()->subDay(),
        'last_validated_at' => now()->subHour(),
    ]);

    $this->postJson('/api/v1/client/licenses/validate', [
        'license_key' => 'raw-client-license',
        'product_code' => $product->code,
        'activation_id' => $activation->activation_code,
        'domain' => 'example.com',
    ], [
        'X-API-Key' => 'test-client-key',
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'ACTIVATION_DEACTIVATED');
});
