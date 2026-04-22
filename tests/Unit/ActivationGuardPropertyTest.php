<?php

namespace Tests\Unit;

use App\Models\Activation;
use App\Models\License;
use App\Models\Product;
use App\Services\ActivationService;
use App\States\License\ActiveState;
use App\States\License\ExpiredState;
use App\States\License\InactiveState;
use App\States\License\RevokedState;
use App\States\License\SuspendedState;
use Carbon\Carbon;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based test for activation guard
 *
 * **Validates: Requirements 4.3, 4.5, 4.8**
 *
 * Property 7: Activation guard rejects invalid license states
 *
 * For any activation request:
 * - If license status is `revoked`, request is rejected with `LICENSE_REVOKED`
 * - If license status is `suspended`, request is rejected with `LICENSE_SUSPENDED`
 * - If license status is `expired`, request is rejected with `LICENSE_EXPIRED`
 * - For per-device licenses, if device fingerprint differs from registered, rejected with `DEVICE_MISMATCH`
 * - For per-user licenses, if user_identifier differs from registered, rejected with `USER_MISMATCH`
 *
 */
class ActivationGuardPropertyTest extends TestCase
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
            'slug'                    => 'test-product-ag-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-ag-' . uniqid(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Property 7a — Invalid license states reject activation
    // -------------------------------------------------------------------------

    /**
     * Property 7a: Revoked, suspended, and expired licenses reject activation
     *
     * For any license in state `revoked`, `suspended`, or `expired`, an activation
     * request SHALL be rejected with the corresponding error code.
     *
     * Uses Eris to generate random device fingerprints and user identifiers to
     * exercise a variety of activation scenarios.
     *
     */
    public function property_revoked_suspended_expired_licenses_reject_activation(): void
    {
        $this->limitTo(100)->forAll(
            // Random device fingerprint
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            ),
            // Random user identifier
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            ),
            // Random license model
            Generator\elements('per-device', 'per-user', 'floating'),
            // Random invalid state
            Generator\elements('revoked', 'suspended', 'expired')
        )->then(function (string $fingerprint, string $userIdentifier, string $model, string $invalidState) {
            // Map state name to state class
            $stateClasses = [
                'revoked'   => RevokedState::class,
                'suspended' => SuspendedState::class,
                'expired'   => ExpiredState::class,
            ];

            $stateClass = $stateClasses[$invalidState];

            // Create a license in the invalid state
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => $model,
                'status'        => new $stateClass(new License()),
                'max_seats'     => 5,
                'expiry_date'   => null,
            ]);

            // Attempt activation
            $threw = false;
            $exceptionMessage = '';
            try {
                $this->service->activate($license, $fingerprint, $userIdentifier, '127.0.0.1');
            } catch (\Exception $e) {
                $threw = true;
                $exceptionMessage = $e->getMessage();
            }

            // Verify that activation was rejected
            $this->assertTrue(
                $threw,
                "Activation of {$invalidState} license should throw an exception, but it did not."
            );

            // Verify the correct error code was thrown
            $expectedErrorCode = 'LICENSE_' . strtoupper($invalidState);
            $this->assertStringContainsString(
                $expectedErrorCode,
                $exceptionMessage,
                "Expected error code '{$expectedErrorCode}' but got '{$exceptionMessage}'"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 7b — Per-device license device mismatch
    // -------------------------------------------------------------------------

    /**
     * Property 7b: Per-device license rejects mismatched device fingerprint
     *
     * For any per-device license that has been activated with device fingerprint F1,
     * an activation request with a different device fingerprint F2 (where F1 != F2)
     * SHALL be rejected with error code `DEVICE_MISMATCH`.
     *
     * Uses Eris to generate random device fingerprints to ensure they differ.
     *
     */
    public function property_per_device_license_rejects_mismatched_fingerprint(): void
    {
        $this->limitTo(100)->forAll(
            // First device fingerprint
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            ),
            // Second device fingerprint (different from first)
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            )
        )->then(function (string $fingerprint1, string $fingerprint2) {
            // Skip if fingerprints are the same (very unlikely but possible)
            if ($fingerprint1 === $fingerprint2) {
                $this->markTestSkipped('Generated fingerprints are identical');
            }

            // Create a per-device license in inactive state
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'per-device',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            // First activation with fingerprint1 should succeed
            $activation1 = $this->service->activate($license, $fingerprint1, null, '127.0.0.1');
            $this->assertNotNull($activation1);

            // Refresh license to get updated state
            $license->refresh();

            // Second activation with fingerprint2 should fail with DEVICE_MISMATCH
            $threw = false;
            $exceptionMessage = '';
            try {
                $this->service->activate($license, $fingerprint2, null, '127.0.0.1');
            } catch (\Exception $e) {
                $threw = true;
                $exceptionMessage = $e->getMessage();
            }

            $this->assertTrue(
                $threw,
                "Activation with mismatched device fingerprint should throw an exception, but it did not."
            );

            $this->assertStringContainsString(
                'DEVICE_MISMATCH',
                $exceptionMessage,
                "Expected error code 'DEVICE_MISMATCH' but got '{$exceptionMessage}'"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 7c — Per-user license user mismatch
    // -------------------------------------------------------------------------

    /**
     * Property 7c: Per-user license rejects mismatched user identifier
     *
     * For any per-user license that has been activated with user identifier U1,
     * an activation request with a different user identifier U2 (where U1 != U2)
     * SHALL be rejected with error code `USER_MISMATCH`.
     *
     * Uses Eris to generate random user identifiers to ensure they differ.
     *
     */
    public function property_per_user_license_rejects_mismatched_user_identifier(): void
    {
        $this->limitTo(100)->forAll(
            // First user identifier
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            ),
            // Second user identifier (different from first)
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            )
        )->then(function (string $user1, string $user2) {
            // Skip if user identifiers are the same (very unlikely but possible)
            if ($user1 === $user2) {
                $this->markTestSkipped('Generated user identifiers are identical');
            }

            // Create a per-user license in inactive state
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'per-user',
                'status'        => new InactiveState(new License()),
                'expiry_date'   => null,
            ]);

            // First activation with user1 should succeed
            $activation1 = $this->service->activate($license, 'fingerprint', $user1, '127.0.0.1');
            $this->assertNotNull($activation1);

            // Refresh license to get updated state
            $license->refresh();

            // Second activation with user2 should fail with USER_MISMATCH
            $threw = false;
            $exceptionMessage = '';
            try {
                $this->service->activate($license, 'fingerprint', $user2, '127.0.0.1');
            } catch (\Exception $e) {
                $threw = true;
                $exceptionMessage = $e->getMessage();
            }

            $this->assertTrue(
                $threw,
                "Activation with mismatched user identifier should throw an exception, but it did not."
            );

            $this->assertStringContainsString(
                'USER_MISMATCH',
                $exceptionMessage,
                "Expected error code 'USER_MISMATCH' but got '{$exceptionMessage}'"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 7d — Floating license with invalid states
    // -------------------------------------------------------------------------

    /**
     * Property 7d: Floating licenses in invalid states reject activation
     *
     * For any floating license in state `revoked`, `suspended`, or `expired`,
     * an activation request SHALL be rejected with the corresponding error code.
     *
     * Uses Eris to generate random device fingerprints and invalid states.
     *
     */
    public function property_floating_license_invalid_states_reject_activation(): void
    {
        $this->limitTo(100)->forAll(
            // Random device fingerprint
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 255,
                Generator\string()
            ),
            // Random invalid state
            Generator\elements('revoked', 'suspended', 'expired')
        )->then(function (string $fingerprint, string $invalidState) {
            // Map state name to state class
            $stateClasses = [
                'revoked'   => RevokedState::class,
                'suspended' => SuspendedState::class,
                'expired'   => ExpiredState::class,
            ];

            $stateClass = $stateClasses[$invalidState];

            // Create a floating license in the invalid state
            $license = License::create([
                'product_id'    => $this->product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'floating',
                'status'        => new $stateClass(new License()),
                'max_seats'     => 5,
                'expiry_date'   => null,
            ]);

            // Attempt activation
            $threw = false;
            $exceptionMessage = '';
            try {
                $this->service->activate($license, $fingerprint, null, '127.0.0.1');
            } catch (\Exception $e) {
                $threw = true;
                $exceptionMessage = $e->getMessage();
            }

            // Verify that activation was rejected
            $this->assertTrue(
                $threw,
                "Activation of floating {$invalidState} license should throw an exception, but it did not."
            );

            // Verify the correct error code was thrown
            $expectedErrorCode = 'LICENSE_' . strtoupper($invalidState);
            $this->assertStringContainsString(
                $expectedErrorCode,
                $exceptionMessage,
                "Expected error code '{$expectedErrorCode}' but got '{$exceptionMessage}'"
            );
        });
    }
}

