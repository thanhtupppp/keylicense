<?php

namespace Tests\Feature\Api;

use App\Models\Activation;
use App\Models\License;
use App\Models\Product;
use App\States\License\InactiveState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseActivationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_per_device_activation_flow(): void
    {
        // Create product
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
        ]);

        // Create license
        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
        ]);

        // Activate license
        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'offline_token',
                    'activation_id',
                    'activated_at',
                    'license_model',
                ],
                'error',
            ]);

        // Verify activation was created
        $this->assertDatabaseHas('activations', [
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'test-device-fp'),
            'type' => 'per-device',
            'is_active' => true,
        ]);

        // Verify license status changed to active
        $license->refresh();
        $this->assertEquals('active', $license->status->getValue());

        // Verify offline token JTI was created
        $this->assertDatabaseHas('offline_token_jti', [
            'license_id' => $license->id,
            'is_revoked' => false,
        ]);
    }

    public function test_idempotent_activation_returns_same_result(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
        ]);

        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
        ]);

        // First activation
        $response1 = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response1->assertStatus(200);
        $activationId1 = $response1->json('data.activation_id');

        // Second activation with same parameters
        $response2 = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response2->assertStatus(200);
        $activationId2 = $response2->json('data.activation_id');

        // Should return the same activation
        $this->assertEquals($activationId1, $activationId2);

        // Should only have one activation record
        $this->assertEquals(1, Activation::where('license_id', $license->id)->count());
    }

    public function test_validate_endpoint_checks_license_status(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
        ]);

        // Activate first
        $activateResponse = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $activateResponse->assertStatus(200);

        // Now validate
        $validateResponse = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $validateResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'license_status' => 'active',
                    'license_model' => 'per-device',
                ],
            ]);

        // Verify last_verified_at was updated
        $activation = Activation::where('license_id', $license->id)->first();
        $this->assertNotNull($activation->last_verified_at);
    }

    public function test_deactivate_endpoint_removes_activation(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
        ]);

        // Activate first
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Deactivate
        $response = $this->postJson('/api/v1/licenses/deactivate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deactivated' => true,
                ],
            ]);

        // Verify activation is no longer active
        $this->assertDatabaseHas('activations', [
            'license_id' => $license->id,
            'is_active' => false,
        ]);

        // Verify license status changed to inactive
        $license->refresh();
        $this->assertEquals('inactive', $license->status->getValue());
    }

    public function test_info_endpoint_returns_license_information(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
            'name' => 'Test Product',
            'slug' => 'test-product',
        ]);

        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'notes' => 'Internal notes - should not be returned',
        ]);

        $response = $this->getJson('/api/v1/licenses/info?license_key=' . $licenseKey, [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'license_key_last4' => 'ABCD',
                    'license_model' => 'per-device',
                    'status' => 'inactive',
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@example.com',
                    'product' => [
                        'name' => 'Test Product',
                        'slug' => 'test-product',
                    ],
                ],
            ])
            ->assertJsonMissing([
                'notes' => 'Internal notes - should not be returned',
            ]);
    }

    public function test_transfer_endpoint_requires_inactive_license(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
        ]);

        // Activate first
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-1',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Try to transfer while still active (should fail)
        $response = $this->postJson('/api/v1/licenses/transfer', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-2',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'TRANSFER_NOT_ALLOWED',
                ],
            ]);
    }

    public function test_heartbeat_endpoint_updates_floating_seat(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'floating',
            'max_seats' => 5,
            'status' => new InactiveState(new License()),
        ]);

        // Activate floating license
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-1',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Send heartbeat
        $response = $this->postJson('/api/v1/licenses/heartbeat', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-1',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'heartbeat_received' => true,
                ],
            ]);

        // Verify floating seat was updated
        $this->assertDatabaseHas('floating_seats', [
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'device-1'),
        ]);
    }
}
