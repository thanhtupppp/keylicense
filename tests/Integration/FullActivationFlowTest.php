<?php

namespace Tests\Integration;

use App\Models\Activation;
use App\Models\AuditLog;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Models\OfflineTokenJti;
use App\Models\Product;
use App\States\License\InactiveState;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Configuration;

/**
 * Integration test for full activation flow end-to-end.
 * Tests the complete flow from API request to database records to offline token response.
 * 
 * Requirements: 4.1, 4.2, 4.4, 4.6, 9.8
 */
class FullActivationFlowTest extends MySQLIntegrationTestCase
{
    /**
     * Test full per-device activation flow end-to-end.
     * 
     */
    public function test_full_per_device_activation_flow_end_to_end(): void
    {
        // Arrange: Create product and license
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
            'slug' => 'test-product',
        ]);

        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);
        $deviceFingerprint = 'device-fp-12345';
        $deviceFpHash = hash('sha256', $deviceFingerprint);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => 'inactive',
        ]);

        // Act: Send activation request
        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => $deviceFingerprint,
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert: Response structure
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
                    'expiry_date',
                ],
                'error',
            ]);

        // Assert: Activation record created
        $this->assertDatabaseHas('activations', [
            'license_id' => $license->id,
            'device_fp_hash' => $deviceFpHash,
            'type' => 'per-device',
            'is_active' => true,
        ]);

        $activation = Activation::where('license_id', $license->id)->first();
        $this->assertNotNull($activation);
        $this->assertNotNull($activation->activated_at);

        // Assert: License status changed to active
        $license->refresh();
        $this->assertEquals('active', $license->status->getValue());

        // Assert: Offline token JTI created
        $this->assertDatabaseHas('offline_token_jti', [
            'license_id' => $license->id,
            'is_revoked' => false,
        ]);

        $jtiRecord = OfflineTokenJti::where('license_id', $license->id)->first();
        $this->assertNotNull($jtiRecord);
        $this->assertNotNull($jtiRecord->expires_at);

        // Assert: Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'ACTIVATION_SUCCESS',
            'subject_type' => 'license',
            'subject_id' => $license->id,
            'result' => 'success',
        ]);

        // Assert: Offline token is valid JWT
        $offlineToken = $response->json('data.offline_token');
        $this->assertNotEmpty($offlineToken);

        // Decode and verify JWT claims
        $payload = json_decode(base64_decode(strtr(explode('.', $offlineToken)[1], '-_', '+/')), true);

        $this->assertSame('license-platform', $payload['iss']);
        $aud = $payload['aud'];
        $this->assertSame($product->slug, is_array($aud) ? $aud[0] : $aud);
        $this->assertSame(hash('sha256', $keyHash), $payload['sub']);
        $this->assertSame($deviceFpHash, $payload['device_fp_hash']);
        $this->assertSame('per-device', $payload['license_model']);
        $this->assertSame($jtiRecord->jti, $payload['jti']);

        // Verify TTL is correct (24 hours = 86400 seconds)
        $this->assertNotNull($payload['exp']);
        $this->assertNotNull($payload['iat']);
    }

    /**
     * Test full per-user activation flow end-to-end.
     * 
     */
    public function test_full_per_user_activation_flow_end_to_end(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 12,
        ]);

        $licenseKey = 'USER-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);
        $userIdentifier = 'user@example.com';
        $deviceFingerprint = 'user-device-12345';

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => '9012',
            'license_model' => 'per-user',
            'status' => 'inactive',
        ]);

        // Act
        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => $deviceFingerprint,
            'user_identifier' => $userIdentifier,
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('activations', [
            'license_id' => $license->id,
            'user_identifier' => $userIdentifier,
            'type' => 'per-user',
            'is_active' => true,
        ]);

        $license->refresh();
        $this->assertEquals('active', $license->status->getValue());

        // Verify JWT TTL is 12 hours
        $offlineToken = $response->json('data.offline_token');
        $payload = json_decode(base64_decode(strtr(explode('.', $offlineToken)[1], '-_', '+/')), true);

        $this->assertNotNull($payload['exp']);
        $this->assertNotNull($payload['iat']);
    }

    /**
     * Test full floating license activation flow end-to-end.
     * 
     */
    public function test_full_floating_license_activation_flow_end_to_end(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
        ]);

        $licenseKey = 'FLOT-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);
        $deviceFingerprint = 'floating-device-1';
        $deviceFpHash = hash('sha256', $deviceFingerprint);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'floating',
            'max_seats' => 5,
            'status' => 'inactive',
        ]);

        // Act
        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => $deviceFingerprint,
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert
        $response->assertStatus(200);

        // Verify Activation record created
        $this->assertDatabaseHas('activations', [
            'license_id' => $license->id,
            'device_fp_hash' => $deviceFpHash,
            'type' => 'floating',
            'is_active' => true,
        ]);

        $activation = Activation::where('license_id', $license->id)->first();

        // Verify FloatingSeat record created
        $this->assertDatabaseHas('floating_seats', [
            'license_id' => $license->id,
            'activation_id' => $activation->id,
            'device_fp_hash' => $deviceFpHash,
        ]);

        $floatingSeat = FloatingSeat::where('license_id', $license->id)->first();
        $this->assertNotNull($floatingSeat->last_heartbeat_at);

        // Verify license is active
        $license->refresh();
        $this->assertEquals('active', $license->status->getValue());

        // Verify JWT contains correct license_model
        $offlineToken = $response->json('data.offline_token');
        $payload = json_decode(base64_decode(strtr(explode('.', $offlineToken)[1], '-_', '+/')), true);

        $this->assertSame('floating', $payload['license_model']);
    }

    /**
     * Test idempotent activation returns same result without creating duplicates.
     * 
     */
    public function test_idempotent_activation_does_not_create_duplicate_records(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
        ]);

        $licenseKey = 'IDEM-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);
        $deviceFingerprint = 'idempotent-device';

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => 'inactive',
        ]);

        // Act: First activation
        $response1 = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => $deviceFingerprint,
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $activationId1 = $response1->json('data.activation_id');
        $token1 = $response1->json('data.offline_token');

        // Act: Second activation with same parameters
        $response2 = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => $deviceFingerprint,
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $activationId2 = $response2->json('data.activation_id');
        $token2 = $response2->json('data.offline_token');

        // Assert: Both responses successful
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Assert: Same activation ID returned
        $this->assertSame($activationId1, $activationId2);

        // Assert: Only one activation record exists
        $activationCount = Activation::where('license_id', $license->id)->count();
        $this->assertSame(1, $activationCount);

        // Assert: New token issued (refresh behavior)
        $this->assertNotEquals($token1, $token2);

        // Assert: Multiple JTI records exist (one for each token)
        $jtiCount = OfflineTokenJti::where('license_id', $license->id)->count();
        $this->assertSame(2, $jtiCount);

        // Assert: Only one ACTIVATION_SUCCESS audit log (not duplicate)
        $auditCount = AuditLog::where('event_type', 'ACTIVATION_SUCCESS')
            ->where('subject_id', $license->id)
            ->count();
        $this->assertSame(1, $auditCount);
    }

    /**
     * Test activation flow with expired license is rejected.
     * 
     */
    public function test_activation_flow_rejects_expired_license(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'EXPD-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => '5678',
            'license_model' => 'per-device',
            'status' => 'expired',
            'expiry_date' => now()->subDays(10),
        ]);

        // Act
        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'some-device',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'LICENSE_EXPIRED',
                ],
            ]);

        // Verify no activation record created
        $this->assertDatabaseMissing('activations', [
            'license_id' => $license->id,
        ]);

        // Verify no JTI record created
        $this->assertDatabaseMissing('offline_token_jti', [
            'license_id' => $license->id,
        ]);
    }

    /**
     * Test activation flow with inactive product is rejected.
     * 
     */
    public function test_activation_flow_rejects_inactive_product(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'inactive', // Product is inactive
        ]);

        $licenseKey = 'PRIN-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
        ]);

        // Act
        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'some-device',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_INACTIVE',
                ],
            ]);

        // Verify no activation record created
        $this->assertDatabaseMissing('activations', [
            'license_id' => $license->id,
        ]);
    }
}

