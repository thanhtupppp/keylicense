<?php

namespace Tests\Unit;

use App\Models\Activation;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Models\Product;
use App\Services\ActivationService;
use App\States\License\InactiveState;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based test for activation idempotency
 *
 * **Validates: Requirements 9.8**
 *
 * Property 14: Activation idempotency
 *
 * For any activation request with the same license key and device fingerprint that has
 * already been successfully activated, re-sending the same request SHALL return the
 * existing offline token (or a refreshed one for the same activation record) without
 * creating a duplicate activation record. The total number of activation records for
 * that license-fingerprint pair SHALL remain exactly one.
 *
 */
class ActivationIdempotencyPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private ActivationService $service;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the audit logger
        $auditLogger = $this->createMock(\App\Contracts\AuditLoggerInterface::class);
        $this->service = new ActivationService($auditLogger);

        $this->product = Product::create([
            'name'                    => 'Test Product',
            'slug'                    => 'test-product-idempotency-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-idempotency-' . uniqid(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Property 14a — Per-device activation idempotency
    // -------------------------------------------------------------------------

    /**
     * Property 14a: Per-device activation is idempotent
     *
     * For any per-device license, sending the same activation request multiple times
     * with the same device fingerprint SHALL always return the same activation record
     * without creating duplicates.
     *
     * Uses Eris to generate random repetition counts (1–50) and verifies that
     * repeated activations don't create new records.
     *
     */
    public function property_per_device_activation_is_idempotent(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of repeated activation attempts (1–50)
            Generator\choose(1, 50)
        )->then(function (int $repetitions) {
            // Create a per-device license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'per-device',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            $fingerprint = 'device-fingerprint-' . uniqid();

            // Perform the same activation multiple times
            $activationIds = [];
            for ($i = 0; $i < $repetitions; $i++) {
                $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');
                $this->assertNotNull($activation);
                $activationIds[] = $activation->id;
            }

            // All activation IDs should be identical
            $uniqueIds = array_unique($activationIds);
            $this->assertCount(
                1,
                $uniqueIds,
                "Expected all {$repetitions} activations to return the same ID, but got " . count($uniqueIds) . " unique IDs"
            );

            // Only one activation record should exist for this license
            $activationCount = Activation::where('license_id', $license->id)->count();
            $this->assertSame(
                1,
                $activationCount,
                "Expected exactly 1 activation record after {$repetitions} repeated requests, but found {$activationCount}"
            );

            // Verify the activation has the correct fingerprint hash
            $activation = Activation::where('license_id', $license->id)->first();
            $this->assertNotNull($activation);
            $this->assertEquals(hash('sha256', $fingerprint), $activation->device_fp_hash);
            $this->assertEquals('per-device', $activation->type);
            $this->assertTrue($activation->is_active);
        });
    }

    // -------------------------------------------------------------------------
    // Property 14b — Per-user activation idempotency
    // -------------------------------------------------------------------------

    /**
     * Property 14b: Per-user activation is idempotent
     *
     * For any per-user license, sending the same activation request multiple times
     * with the same user identifier SHALL always return the same activation record
     * without creating duplicates.
     *
     * Uses Eris to generate random repetition counts (1–50) and verifies that
     * repeated activations don't create new records.
     *
     */
    public function property_per_user_activation_is_idempotent(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of repeated activation attempts (1–50)
            Generator\choose(1, 50)
        )->then(function (int $repetitions) {
            // Create a per-user license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '5678',
                'license_model' => 'per-user',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            $userIdentifier = 'user-' . uniqid() . '@example.com';

            // Perform the same activation multiple times
            $activationIds = [];
            for ($i = 0; $i < $repetitions; $i++) {
                $activation = $this->service->activate($license, 'fingerprint-' . $i, $userIdentifier, '127.0.0.1');
                $this->assertNotNull($activation);
                $activationIds[] = $activation->id;
            }

            // All activation IDs should be identical
            $uniqueIds = array_unique($activationIds);
            $this->assertCount(
                1,
                $uniqueIds,
                "Expected all {$repetitions} activations to return the same ID, but got " . count($uniqueIds) . " unique IDs"
            );

            // Only one activation record should exist for this license
            $activationCount = Activation::where('license_id', $license->id)->count();
            $this->assertSame(
                1,
                $activationCount,
                "Expected exactly 1 activation record after {$repetitions} repeated requests, but found {$activationCount}"
            );

            // Verify the activation has the correct user identifier
            $activation = Activation::where('license_id', $license->id)->first();
            $this->assertNotNull($activation);
            $this->assertEquals($userIdentifier, $activation->user_identifier);
            $this->assertEquals('per-user', $activation->type);
            $this->assertTrue($activation->is_active);
        });
    }

    // -------------------------------------------------------------------------
    // Property 14c — Floating activation idempotency
    // -------------------------------------------------------------------------

    /**
     * Property 14c: Floating activation is idempotent
     *
     * For any floating license, sending the same activation request multiple times
     * with the same device fingerprint SHALL always return the same activation record
     * and create only one FloatingSeat, without consuming additional seats.
     *
     * Uses Eris to generate random repetition counts (1–50) and verifies that
     * repeated activations don't create new seats.
     *
     */
    public function property_floating_activation_is_idempotent(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of repeated activation attempts (1–50)
            Generator\choose(1, 50)
        )->then(function (int $repetitions) {
            // Create a floating license with sufficient seats
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '9999',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => 100,
                'expiry_date'   => null,
            ]);

            $fingerprint = 'device-fingerprint-' . uniqid();

            // Perform the same activation multiple times
            $activationIds = [];
            for ($i = 0; $i < $repetitions; $i++) {
                $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');
                $this->assertNotNull($activation);
                $activationIds[] = $activation->id;
            }

            // All activation IDs should be identical
            $uniqueIds = array_unique($activationIds);
            $this->assertCount(
                1,
                $uniqueIds,
                "Expected all {$repetitions} activations to return the same ID, but got " . count($uniqueIds) . " unique IDs"
            );

            // Only one activation record should exist for this license
            $activationCount = Activation::where('license_id', $license->id)->count();
            $this->assertSame(
                1,
                $activationCount,
                "Expected exactly 1 activation record after {$repetitions} repeated requests, but found {$activationCount}"
            );

            // Only one floating seat should exist
            $seatCount = FloatingSeat::where('license_id', $license->id)->count();
            $this->assertSame(
                1,
                $seatCount,
                "Expected exactly 1 floating seat after {$repetitions} repeated requests, but found {$seatCount}"
            );

            // Verify the activation and seat have the correct fingerprint hash
            $activation = Activation::where('license_id', $license->id)->first();
            $this->assertNotNull($activation);
            $this->assertEquals(hash('sha256', $fingerprint), $activation->device_fp_hash);
            $this->assertEquals('floating', $activation->type);
            $this->assertTrue($activation->is_active);

            $seat = FloatingSeat::where('license_id', $license->id)->first();
            $this->assertNotNull($seat);
            $this->assertEquals(hash('sha256', $fingerprint), $seat->device_fp_hash);
            $this->assertEquals($activation->id, $seat->activation_id);
        });
    }

    // -------------------------------------------------------------------------
    // Property 14d — Floating idempotency with multiple seats
    // -------------------------------------------------------------------------

    /**
     * Property 14d: Multiple floating seats are independent
     *
     * For any floating license, different device fingerprints SHALL create
     * separate activation records and seats (idempotency applies only to the
     * same fingerprint, not across different fingerprints).
     *
     * Uses Eris to generate random numbers of different fingerprints and verifies
     * that each creates a separate activation and seat.
     *
     */
    public function property_different_fingerprints_create_different_floating_seats(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of different fingerprints (1–20)
            Generator\choose(1, 20)
        )->then(function (int $fingerprintCount) {
            // Create a floating license with sufficient seats
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '4321',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => $fingerprintCount + 10,
                'expiry_date'   => null,
            ]);

            // Activate with different fingerprints
            $activationIds = [];
            for ($i = 0; $i < $fingerprintCount; $i++) {
                $fingerprint = 'device-fingerprint-' . $i . '-' . uniqid();
                $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');
                $this->assertNotNull($activation);
                $activationIds[] = $activation->id;
            }

            // All activation IDs should be unique (one per fingerprint)
            $uniqueIds = array_unique($activationIds);
            $this->assertCount(
                $fingerprintCount,
                $uniqueIds,
                "Expected {$fingerprintCount} unique activation IDs for {$fingerprintCount} different fingerprints, but got " . count($uniqueIds)
            );

            // Exactly fingerprintCount activation records should exist
            $activationCount = Activation::where('license_id', $license->id)->count();
            $this->assertSame(
                $fingerprintCount,
                $activationCount,
                "Expected exactly {$fingerprintCount} activation records, but found {$activationCount}"
            );

            // Exactly fingerprintCount floating seats should exist
            $seatCount = FloatingSeat::where('license_id', $license->id)->count();
            $this->assertSame(
                $fingerprintCount,
                $seatCount,
                "Expected exactly {$fingerprintCount} floating seats, but found {$seatCount}"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 14e — Idempotency preserves activation state
    // -------------------------------------------------------------------------

    /**
     * Property 14e: Repeated activations preserve activation state
     *
     * For any license that has been activated, repeated activation requests
     * SHALL preserve the original activation's state (activated_at, is_active, etc.)
     * and not update timestamps unnecessarily.
     *
     * Uses Eris to generate random repetition counts and verifies that the
     * activation state remains consistent.
     *
     */
    public function property_repeated_activations_preserve_state(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of repeated activation attempts (2–50)
            Generator\choose(2, 50)
        )->then(function (int $repetitions) {
            // Create a per-device license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '7777',
                'license_model' => 'per-device',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            $fingerprint = 'device-fingerprint-' . uniqid();

            // First activation
            $firstActivation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');
            $originalActivatedAt = $firstActivation->activated_at;
            $originalCreatedAt = $firstActivation->created_at;

            // Wait a tiny bit to ensure timestamps would differ if updated
            usleep(100);

            // Perform repeated activations
            for ($i = 1; $i < $repetitions; $i++) {
                $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');

                // Verify the activation ID is the same
                $this->assertEquals($firstActivation->id, $activation->id);

                // Verify the activation state is preserved
                $this->assertEquals($originalActivatedAt, $activation->activated_at);
                $this->assertEquals($originalCreatedAt, $activation->created_at);
                $this->assertTrue($activation->is_active);
            }

            // Verify in database that only one record exists
            $activationCount = Activation::where('license_id', $license->id)->count();
            $this->assertSame(1, $activationCount);

            // Verify the database record matches the original state
            $dbActivation = Activation::where('license_id', $license->id)->first();
            $this->assertEquals($originalActivatedAt, $dbActivation->activated_at);
            $this->assertEquals($originalCreatedAt, $dbActivation->created_at);
        });
    }

    // -------------------------------------------------------------------------
    // Property 14f — Idempotency with concurrent-like requests
    // -------------------------------------------------------------------------

    /**
     * Property 14f: Rapid sequential activations (simulating concurrency) are idempotent
     *
     * For any license, rapid sequential activation requests with the same fingerprint
     * (simulating concurrent requests) SHALL result in exactly one activation record
     * and consistent behavior.
     *
     * Uses Eris to generate random repetition counts and verifies that even with
     * rapid requests, idempotency is maintained.
     *
     */
    public function property_rapid_sequential_activations_are_idempotent(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of rapid activation attempts (1–100)
            Generator\choose(1, 100)
        )->then(function (int $rapidAttempts) {
            // Create a per-device license
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '8888',
                'license_model' => 'per-device',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            $fingerprint = 'device-fingerprint-' . uniqid();

            // Perform rapid sequential activations
            $activationIds = [];
            for ($i = 0; $i < $rapidAttempts; $i++) {
                $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');
                $activationIds[] = $activation->id;
            }

            // All activation IDs should be identical
            $uniqueIds = array_unique($activationIds);
            $this->assertCount(
                1,
                $uniqueIds,
                "Expected all {$rapidAttempts} rapid activations to return the same ID, but got " . count($uniqueIds) . " unique IDs"
            );

            // Only one activation record should exist
            $activationCount = Activation::where('license_id', $license->id)->count();
            $this->assertSame(
                1,
                $activationCount,
                "Expected exactly 1 activation record after {$rapidAttempts} rapid requests, but found {$activationCount}"
            );
        });
    }
}

