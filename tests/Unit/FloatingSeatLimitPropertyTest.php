<?php

namespace Tests\Unit;

use App\Exceptions\SeatsExhaustedException;
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
 * Property-based test for floating license seat limit enforcement
 *
 * **Validates: Requirements 4.6, 4.7, 9.9**
 *
 * Property 8: Floating license seat limit is strictly enforced
 *
 * For any floating license with `max_seats = N`:
 * - Exactly N concurrent activation requests SHALL succeed (each receiving a seat)
 * - Any subsequent activation request while all N seats are occupied SHALL be rejected with `SEATS_EXHAUSTED`
 * - This property holds even under concurrent request load
 *
 */
class FloatingSeatLimitPropertyTest extends TestCase
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
            'slug'                    => 'test-product-floating-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-floating-' . uniqid(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Property 8a — Exactly N seats can be allocated
    // -------------------------------------------------------------------------

    /**
     * Property 8a: Exactly N activations succeed for floating license with max_seats = N
     *
     * For any floating license with `max_seats = N`, exactly N activation requests
     * with different device fingerprints SHALL succeed, creating N FloatingSeat records.
     *
     * Uses Eris to generate random max_seats values (1–100) and verifies that
     * exactly that many activations succeed.
     *
     */
    public function property_exactly_n_seats_can_be_allocated(): void
    {
        $this->limitTo(100)->forAll(
            // Random max_seats from 1 to 100
            Generator\choose(1, 100)
        )->then(function (int $maxSeats) {
            // Create a floating license with max_seats
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => $maxSeats,
                'expiry_date'   => null,
            ]);

            // Attempt to activate exactly N times with different fingerprints
            $successCount = 0;
            for ($i = 0; $i < $maxSeats; $i++) {
                $fingerprint = "device-fingerprint-{$i}-" . uniqid();
                try {
                    $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');
                    $this->assertNotNull($activation);
                    $successCount++;
                } catch (\Exception $e) {
                    $this->fail("Activation {$i} of {$maxSeats} should succeed, but got error: {$e->getMessage()}");
                }
            }

            // Verify exactly N activations succeeded
            $this->assertSame($maxSeats, $successCount);

            // Verify exactly N floating seats exist
            $seatCount = FloatingSeat::where('license_id', $license->id)->count();
            $this->assertSame($maxSeats, $seatCount);
        });
    }

    // -------------------------------------------------------------------------
    // Property 8b — Activation N+1 is rejected with SEATS_EXHAUSTED
    // -------------------------------------------------------------------------

    /**
     * Property 8b: Activation N+1 is rejected with SEATS_EXHAUSTED
     *
     * For any floating license with `max_seats = N`, after N successful activations,
     * the (N+1)th activation request SHALL be rejected with `SEATS_EXHAUSTED` exception.
     *
     * Uses Eris to generate random max_seats values (1–100) and verifies that
     * the (N+1)th activation fails with the correct error.
     *
     */
    public function property_activation_n_plus_1_is_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Random max_seats from 1 to 100
            Generator\choose(1, 100)
        )->then(function (int $maxSeats) {
            // Create a floating license with max_seats
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => $maxSeats,
                'expiry_date'   => null,
            ]);

            // Activate exactly N times
            for ($i = 0; $i < $maxSeats; $i++) {
                $fingerprint = "device-fingerprint-{$i}-" . uniqid();
                $this->service->activate($license, $fingerprint, null, '127.0.0.1');
            }

            // Refresh license to get updated state
            $license->refresh();

            // Attempt (N+1)th activation — should fail with SEATS_EXHAUSTED
            $fingerprint = "device-fingerprint-exhausted-" . uniqid();
            $threw = false;
            $exceptionMessage = '';
            try {
                $this->service->activate($license, $fingerprint, null, '127.0.0.1');
            } catch (SeatsExhaustedException $e) {
                $threw = true;
                $exceptionMessage = $e->getMessage();
            } catch (\Exception $e) {
                $this->fail("Expected SeatsExhaustedException but got " . get_class($e) . ": {$e->getMessage()}");
            }

            // Verify that the (N+1)th activation was rejected
            $this->assertTrue(
                $threw,
                "Activation " . ($maxSeats + 1) . " should be rejected with SEATS_EXHAUSTED, but it succeeded."
            );

            // Verify the exception message contains the max_seats info
            $this->assertStringContainsString(
                (string)$maxSeats,
                $exceptionMessage,
                "Exception message should mention max_seats ({$maxSeats})"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 8c — Seat count matches activation count
    // -------------------------------------------------------------------------

    /**
     * Property 8c: Floating seat count always matches active activation count
     *
     * For any floating license after K successful activations (where K <= max_seats),
     * the number of FloatingSeat records SHALL equal K.
     *
     * Uses Eris to generate random max_seats and random activation counts,
     * and verifies that the seat count matches the activation count.
     *
     */
    public function property_seat_count_matches_activation_count(): void
    {
        $this->limitTo(100)->forAll(
            // Random max_seats from 1 to 100
            Generator\choose(1, 100),
            // Random activation count (will be clamped to max_seats)
            Generator\choose(0, 100)
        )->then(function (int $maxSeats, int $activationCount) {
            // Clamp activation count to max_seats
            $activationCount = min($activationCount, $maxSeats);

            // Create a floating license with max_seats
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => $maxSeats,
                'expiry_date'   => null,
            ]);

            // Activate exactly activationCount times
            for ($i = 0; $i < $activationCount; $i++) {
                $fingerprint = "device-fingerprint-{$i}-" . uniqid();
                $this->service->activate($license, $fingerprint, null, '127.0.0.1');
            }

            // Verify seat count matches activation count
            $seatCount = FloatingSeat::where('license_id', $license->id)->count();
            $this->assertSame(
                $activationCount,
                $seatCount,
                "After {$activationCount} activations, expected {$activationCount} seats but found {$seatCount}"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 8d — Concurrent activations respect seat limit
    // -------------------------------------------------------------------------

    /**
     * Property 8d: Concurrent activations respect the seat limit
     *
     * For any floating license with `max_seats = N`, even when multiple activation
     * requests are processed concurrently (simulated via rapid sequential requests),
     * the total number of allocated seats SHALL never exceed N.
     *
     * Uses Eris to generate random max_seats and verifies that concurrent-like
     * activation attempts don't exceed the limit.
     *
     */
    public function property_concurrent_activations_respect_limit(): void
    {
        $this->limitTo(100)->forAll(
            // Random max_seats from 1 to 50 (smaller range for concurrency test)
            Generator\choose(1, 50)
        )->then(function (int $maxSeats) {
            // Create a floating license with max_seats
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => $maxSeats,
                'expiry_date'   => null,
            ]);

            // Attempt to activate maxSeats + 10 times (simulating concurrent requests)
            $successCount = 0;
            $failureCount = 0;
            for ($i = 0; $i < $maxSeats + 10; $i++) {
                $fingerprint = "device-fingerprint-{$i}-" . uniqid();
                try {
                    $this->service->activate($license, $fingerprint, null, '127.0.0.1');
                    $successCount++;
                } catch (SeatsExhaustedException $e) {
                    $failureCount++;
                } catch (\Exception $e) {
                    $this->fail("Unexpected exception: {$e->getMessage()}");
                }
            }

            // Verify exactly maxSeats succeeded
            $this->assertSame(
                $maxSeats,
                $successCount,
                "Expected exactly {$maxSeats} successful activations, got {$successCount}"
            );

            // Verify exactly 10 failed with SEATS_EXHAUSTED
            $this->assertSame(
                10,
                $failureCount,
                "Expected exactly 10 failed activations, got {$failureCount}"
            );

            // Verify seat count never exceeds maxSeats
            $seatCount = FloatingSeat::where('license_id', $license->id)->count();
            $this->assertLessThanOrEqual(
                $maxSeats,
                $seatCount,
                "Seat count ({$seatCount}) exceeded max_seats ({$maxSeats})"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 8e — Different max_seats values are independent
    // -------------------------------------------------------------------------

    /**
     * Property 8e: Different licenses with different max_seats are independent
     *
     * For any two floating licenses with different max_seats values (N1 and N2),
     * activations on license 1 SHALL not affect the seat limit of license 2.
     *
     * Uses Eris to generate two different max_seats values and verifies that
     * each license respects its own limit independently.
     *
     */
    public function property_different_licenses_have_independent_limits(): void
    {
        $this->limitTo(100)->forAll(
            // First license max_seats
            Generator\choose(1, 50),
            // Second license max_seats
            Generator\choose(1, 50)
        )->then(function (int $maxSeats1, int $maxSeats2) {
            // Create two floating licenses with different max_seats
            $license1 = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-1-' . uniqid()),
                'key_last4'     => '1111',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => $maxSeats1,
                'expiry_date'   => null,
            ]);

            $license2 = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-2-' . uniqid()),
                'key_last4'     => '2222',
                'license_model' => 'floating',
                'status'        => new InactiveState(new License()),
                'max_seats'     => $maxSeats2,
                'expiry_date'   => null,
            ]);

            // Activate maxSeats1 times on license1
            for ($i = 0; $i < $maxSeats1; $i++) {
                $fingerprint = "license1-device-{$i}-" . uniqid();
                $this->service->activate($license1, $fingerprint, null, '127.0.0.1');
            }

            // Activate maxSeats2 times on license2
            for ($i = 0; $i < $maxSeats2; $i++) {
                $fingerprint = "license2-device-{$i}-" . uniqid();
                $this->service->activate($license2, $fingerprint, null, '127.0.0.1');
            }

            // Verify license1 has exactly maxSeats1 seats
            $seats1 = FloatingSeat::where('license_id', $license1->id)->count();
            $this->assertSame($maxSeats1, $seats1);

            // Verify license2 has exactly maxSeats2 seats
            $seats2 = FloatingSeat::where('license_id', $license2->id)->count();
            $this->assertSame($maxSeats2, $seats2);

            // Attempt one more activation on license1 — should fail
            $license1->refresh();
            $threw1 = false;
            try {
                $this->service->activate($license1, 'extra-device-1-' . uniqid(), null, '127.0.0.1');
            } catch (SeatsExhaustedException $e) {
                $threw1 = true;
            }
            $this->assertTrue($threw1, "License1 should reject activation when full");

            // Attempt one more activation on license2 — should fail
            $license2->refresh();
            $threw2 = false;
            try {
                $this->service->activate($license2, 'extra-device-2-' . uniqid(), null, '127.0.0.1');
            } catch (SeatsExhaustedException $e) {
                $threw2 = true;
            }
            $this->assertTrue($threw2, "License2 should reject activation when full");
        });
    }
}

