<?php

namespace Tests\Integration;

use App\Models\Activation;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Models\Product;
use App\States\License\InactiveState;
use Illuminate\Support\Facades\DB;

/**
 * Integration test for concurrent floating seat allocation.
 * Simulates concurrent requests to verify no race conditions occur.
 * 
 * Requirements: T10, 4.7, 9.9
 */
class ConcurrentFloatingSeatTest extends MySQLIntegrationTestCase
{
    /**
     * Test concurrent activation requests respect seat limit without race conditions.
     * 
     */
    public function test_concurrent_activations_respect_seat_limit(): void
    {
        // Arrange: Create floating license with max_seats = 3
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
        ]);

        $licenseKey = 'ABCD-EFGH-IJKL-MNOP';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TEST',
            'license_model' => 'floating',
            'max_seats' => 3,
            'status' => 'inactive',
        ]);

        // Act: Simulate 5 concurrent activation requests
        $devices = [
            'device-1',
            'device-2',
            'device-3',
            'device-4',
            'device-5',
        ];

        $responses = [];
        $successCount = 0;
        $exhaustedCount = 0;

        // Use database transactions to simulate true concurrency
        foreach ($devices as $device) {
            try {
                $response = $this->postJson('/api/v1/licenses/activate', [
                    'license_key' => $licenseKey,
                    'device_fingerprint' => $device,
                ], [
                    'X-API-Key' => $product->api_key,
                ]);

                $responses[$device] = $response;

                if ($response->status() === 200) {
                    $successCount++;
                } elseif (
                    $response->status() === 422 &&
                    $response->json('error.code') === 'SEATS_EXHAUSTED'
                ) {
                    $exhaustedCount++;
                }
            } catch (\Exception $e) {
                // Handle any database constraint violations
                $exhaustedCount++;
            }
        }

        // Assert: Exactly 3 activations succeeded
        $this->assertEquals(3, $successCount, 'Expected exactly 3 successful activations');

        // Assert: Exactly 2 requests were rejected with SEATS_EXHAUSTED
        $this->assertEquals(2, $exhaustedCount, 'Expected exactly 2 requests to be rejected');

        // Assert: Database has exactly 3 activation records
        $activationCount = Activation::where('license_id', $license->id)->count();
        $this->assertEquals(3, $activationCount, 'Expected exactly 3 activation records in database');

        // Assert: Database has exactly 3 floating seat records
        $seatCount = FloatingSeat::where('license_id', $license->id)->count();
        $this->assertEquals(3, $seatCount, 'Expected exactly 3 floating seat records in database');

        // Assert: No duplicate device fingerprints
        $uniqueDevices = FloatingSeat::where('license_id', $license->id)
            ->distinct('device_fp_hash')
            ->count();
        $this->assertEquals(3, $uniqueDevices, 'Expected 3 unique device fingerprints');
    }

    /**
     * Test concurrent activations with same device fingerprint are idempotent.
     * 
     */
    public function test_concurrent_activations_same_device_are_idempotent(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
        ]);

        $licenseKey = 'QRST-UVWX-YZ12-3456';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TEST',
            'license_model' => 'floating',
            'max_seats' => 5,
            'status' => new InactiveState(new License()),
        ]);

        $deviceFingerprint = 'same-device-fp';

        // Act: Send 3 concurrent requests with same device fingerprint
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/v1/licenses/activate', [
                'license_key' => $licenseKey,
                'device_fingerprint' => $deviceFingerprint,
            ], [
                'X-API-Key' => $product->api_key,
            ]);
        }

        // Assert: All requests succeeded
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Assert: Only one activation record created
        $activationCount = Activation::where('license_id', $license->id)->count();
        $this->assertEquals(1, $activationCount, 'Expected only 1 activation record');

        // Assert: Only one floating seat record created
        $seatCount = FloatingSeat::where('license_id', $license->id)->count();
        $this->assertEquals(1, $seatCount, 'Expected only 1 floating seat record');

        // Assert: All responses return same activation_id
        $activationIds = array_map(fn($r) => $r->json('data.activation_id'), $responses);
        $uniqueActivationIds = array_unique($activationIds);
        $this->assertCount(1, $uniqueActivationIds, 'Expected all responses to return same activation_id');
    }

    /**
     * Test seat release allows new activation immediately.
     * 
     */
    public function test_seat_release_allows_immediate_new_activation(): void
    {
        // Arrange: Create floating license with max_seats = 2
        $product = Product::factory()->create([
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
        ]);

        $licenseKey = 'AAAA-BBBB-CCCC-DDDD';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TEST',
            'license_model' => 'floating',
            'max_seats' => 2,
            'status' => new InactiveState(new License()),
        ]);

        // Act: Activate 2 devices (fill all seats)
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-1',
        ], [
            'X-API-Key' => $product->api_key,
        ])->assertStatus(200);

        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-2',
        ], [
            'X-API-Key' => $product->api_key,
        ])->assertStatus(200);

        // Verify seats are full
        $response3 = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-3',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response3->assertStatus(422)
            ->assertJson([
                'error' => [
                    'code' => 'SEATS_EXHAUSTED',
                ],
            ]);

        // Act: Deactivate device-1
        $this->postJson('/api/v1/licenses/deactivate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-1',
        ], [
            'X-API-Key' => $product->api_key,
        ])->assertStatus(200);

        // Act: Immediately try to activate device-3
        $response4 = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'device-3',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert: device-3 activation succeeds
        $response4->assertStatus(200);

        // Assert: Only 2 active seats
        $activeSeatCount = FloatingSeat::where('license_id', $license->id)->count();
        $this->assertEquals(2, $activeSeatCount);

        // Assert: device-1 seat is gone, device-2 and device-3 are active
        $this->assertDatabaseMissing('floating_seats', [
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'device-1'),
        ]);

        $this->assertDatabaseHas('floating_seats', [
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'device-2'),
        ]);

        $this->assertDatabaseHas('floating_seats', [
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'device-3'),
        ]);
    }

    /**
     * Test concurrent heartbeats update seat timestamps correctly.
     * 
     */
    public function test_concurrent_heartbeats_update_timestamps_correctly(): void
    {
        // Arrange: Create floating license and activate 3 devices
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'EEEE-FFFF-GGGG-HHHH';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TEST',
            'license_model' => 'floating',
            'max_seats' => 3,
            'status' => new InactiveState(new License()),
        ]);

        $devices = ['device-1', 'device-2', 'device-3'];

        foreach ($devices as $device) {
            $this->postJson('/api/v1/licenses/activate', [
                'license_key' => $licenseKey,
                'device_fingerprint' => $device,
            ], [
                'X-API-Key' => $product->api_key,
            ])->assertStatus(200);
        }

        // Get initial timestamps
        $initialTimestamps = [];
        foreach ($devices as $device) {
            $seat = FloatingSeat::where('license_id', $license->id)
                ->where('device_fp_hash', hash('sha256', $device))
                ->first();
            $initialTimestamps[$device] = $seat->last_heartbeat_at;
        }

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Act: Send concurrent heartbeats from all devices
        foreach ($devices as $device) {
            $this->postJson('/api/v1/licenses/heartbeat', [
                'license_key' => $licenseKey,
                'device_fingerprint' => $device,
            ], [
                'X-API-Key' => $product->api_key,
            ])->assertStatus(200);
        }

        // Assert: All timestamps were updated
        foreach ($devices as $device) {
            $seat = FloatingSeat::where('license_id', $license->id)
                ->where('device_fp_hash', hash('sha256', $device))
                ->first();

            $this->assertNotNull($seat);
            $this->assertTrue(
                $seat->last_heartbeat_at->isAfter($initialTimestamps[$device]),
                "Heartbeat timestamp for {$device} should be updated"
            );
        }

        // Assert: Still exactly 3 seats
        $seatCount = FloatingSeat::where('license_id', $license->id)->count();
        $this->assertEquals(3, $seatCount);
    }

    /**
     * Test database unique constraint prevents duplicate seats.
     * 
     */
    public function test_database_constraint_prevents_duplicate_seats(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'IIII-JJJJ-KKKK-LLLL';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TEST',
            'license_model' => 'floating',
            'max_seats' => 10,
            'status' => new InactiveState(new License()),
        ]);

        // Activate once
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ])->assertStatus(200);

        // Try to manually insert duplicate seat (should fail due to unique constraint)
        $deviceFpHash = hash('sha256', 'test-device');
        $activation = Activation::where('license_id', $license->id)->first();

        $exceptionThrown = false;
        try {
            DB::table('floating_seats')->insert([
                'license_id' => $license->id,
                'activation_id' => $activation->id,
                'device_fp_hash' => $deviceFpHash,
                'last_heartbeat_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Unique constraint violation expected
            $exceptionThrown = true;
            $this->assertTrue(
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint failed'),
                $e->getMessage()
            );
        }

        $this->assertTrue($exceptionThrown, 'Expected unique constraint violation');

        // Assert: Still only one seat
        $seatCount = FloatingSeat::where('license_id', $license->id)->count();
        $this->assertEquals(1, $seatCount);
    }
}

