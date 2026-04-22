<?php

namespace Tests\Unit;

use App\Models\License;
use App\Models\Product;
use App\Services\ActivationService;
use App\States\License\ActiveState;
use App\States\License\ExpiredState;
use App\States\License\InactiveState;
use App\States\License\RevokedState;
use App\States\License\SuspendedState;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Property-based test for inactive product blocking activations
 *
 * **Validates: Requirements 1.9, 1.11**
 *
 * Property 3: Inactive product blocks new activations
 *
 * For any license key belonging to a product with status 'inactive', an activation
 * request SHALL always be rejected with error code 'PRODUCT_INACTIVE', regardless
 * of the license key's own status or the device fingerprint provided.
 *
 */
class InactiveProductBlocksActivationPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private ActivationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the audit logger
        $auditLogger = $this->createMock(\App\Contracts\AuditLoggerInterface::class);
        $this->service = new ActivationService($auditLogger);
    }

    // -------------------------------------------------------------------------
    // Property 3 — Inactive product blocks all activations
    // -------------------------------------------------------------------------

    /**
     * Property 3: Inactive product blocks new activations
     *
     * For any license belonging to a product with status 'inactive', an activation
     * request SHALL always be rejected with error code 'PRODUCT_INACTIVE', regardless
     * of:
     * - The license's own status (inactive, active, expired, suspended, revoked)
     * - The device fingerprint provided
     * - The user identifier provided
     * - The license model (per-device, per-user, floating)
     *
     * This test simulates the full API flow by creating a request and calling the
     * LicenseController's activate method, which checks product status before
     * delegating to ActivationService.
     *
     */
    public function property_inactive_product_blocks_all_activations(): void
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
            // Random license status
            Generator\elements('inactive', 'active', 'expired', 'suspended', 'revoked')
        )->then(function (string $fingerprint, string $userIdentifier, string $model, string $licenseStatus) {
            // Create an inactive product
            $product = Product::create([
                'name'                    => 'Test Product',
                'slug'                    => 'test-product-inactive-' . uniqid(),
                'status'                  => 'inactive', // Product is inactive
                'offline_token_ttl_hours' => 24,
                'api_key'                 => 'test-api-key-inactive-' . uniqid(),
            ]);

            // Map license status to state class
            $stateClasses = [
                'inactive'  => InactiveState::class,
                'active'    => ActiveState::class,
                'expired'   => ExpiredState::class,
                'suspended' => SuspendedState::class,
                'revoked'   => RevokedState::class,
            ];

            $stateClass = $stateClasses[$licenseStatus];

            // Generate a unique license key
            $licenseKey = $this->generateLicenseKey();
            $keyHash = hash('sha256', $licenseKey);

            // Create a license with the given status
            $license = License::create([
                'product_id'    => $product->id,
                'key_hash'      => $keyHash,
                'key_last4'     => substr($licenseKey, -4),
                'license_model' => $model,
                'status'        => new $stateClass(new License()),
                'max_seats'     => 5,
                'expiry_date'   => null,
            ]);

            // Simulate API request
            $request = Request::create('/api/v1/licenses/activate', 'POST', [
                'license_key'        => $licenseKey,
                'device_fingerprint' => $fingerprint,
                'user_identifier'    => $userIdentifier,
            ]);

            // Set the product in request attributes (simulating auth:api_key middleware)
            $request->attributes->set('product', $product);

            // Set IP address
            $request->server->set('REMOTE_ADDR', '127.0.0.1');

            // Create controller instance
            $offlineTokenService = $this->createMock(\App\Services\OfflineTokenService::class);
            $controller = new \App\Http\Controllers\Api\LicenseController(
                $this->service,
                $offlineTokenService
            );

            // Call the activate method
            $response = $controller->activate($request);

            // Verify that the response indicates product is inactive
            $responseData = json_decode($response->getContent(), true);

            $this->assertFalse(
                $responseData['success'],
                "Activation should fail when product is inactive, but it succeeded."
            );

            $this->assertEquals(
                'PRODUCT_INACTIVE',
                $responseData['error']['code'],
                "Expected error code 'PRODUCT_INACTIVE' but got '{$responseData['error']['code']}'"
            );

            $this->assertEquals(
                422,
                $response->getStatusCode(),
                "Expected HTTP status 422 but got {$response->getStatusCode()}"
            );

            // Verify that no activation was created
            $activationCount = \App\Models\Activation::where('license_id', $license->id)->count();
            $this->assertEquals(
                0,
                $activationCount,
                "No activation should be created when product is inactive, but found {$activationCount} activation(s)."
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 3b — Active product allows activations (control test)
    // -------------------------------------------------------------------------

    /**
     * Property 3b: Active product allows valid activations (control test)
     *
     * This is a control test to verify that when a product is 'active', valid
     * activation requests succeed. This ensures our test setup is correct and
     * the PRODUCT_INACTIVE check is the only thing blocking activations in the
     * main property test.
     *
     */
    public function property_active_product_allows_valid_activations(): void
    {
        $this->limitTo(50)->forAll(
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
            Generator\elements('per-device', 'per-user', 'floating')
        )->then(function (string $fingerprint, string $userIdentifier, string $model) {
            // Create an active product
            $product = Product::create([
                'name'                    => 'Test Product',
                'slug'                    => 'test-product-active-' . uniqid(),
                'status'                  => 'active', // Product is active
                'offline_token_ttl_hours' => 24,
                'api_key'                 => 'test-api-key-active-' . uniqid(),
            ]);

            // Generate a unique license key
            $licenseKey = $this->generateLicenseKey();
            $keyHash = hash('sha256', $licenseKey);

            // Create a license in inactive state (ready for activation)
            $license = License::create([
                'product_id'    => $product->id,
                'key_hash'      => $keyHash,
                'key_last4'     => substr($licenseKey, -4),
                'license_model' => $model,
                'status'        => new InactiveState(new License()),
                'max_seats'     => 5,
                'expiry_date'   => null,
            ]);

            // Simulate API request
            $request = Request::create('/api/v1/licenses/activate', 'POST', [
                'license_key'        => $licenseKey,
                'device_fingerprint' => $fingerprint,
                'user_identifier'    => $userIdentifier,
            ]);

            // Set the product in request attributes
            $request->attributes->set('product', $product);

            // Set IP address
            $request->server->set('REMOTE_ADDR', '127.0.0.1');

            // Mock OfflineTokenService to return a dummy token
            $offlineTokenService = $this->createMock(\App\Services\OfflineTokenService::class);
            $offlineTokenService->method('issue')->willReturn('dummy-offline-token');

            // Create controller instance
            $controller = new \App\Http\Controllers\Api\LicenseController(
                $this->service,
                $offlineTokenService
            );

            // Call the activate method
            $response = $controller->activate($request);

            // Verify that the response indicates success
            $responseData = json_decode($response->getContent(), true);

            $this->assertTrue(
                $responseData['success'],
                "Activation should succeed when product is active and license is valid, but it failed with: " .
                    ($responseData['error']['code'] ?? 'unknown error')
            );

            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Expected HTTP status 200 but got {$response->getStatusCode()}"
            );

            // Verify that an activation was created
            $activationCount = \App\Models\Activation::where('license_id', $license->id)->count();
            $this->assertGreaterThan(
                0,
                $activationCount,
                "An activation should be created when product is active and license is valid."
            );
        });
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Generate a random license key in the format XXXX-XXXX-XXXX-XXXX
     *
     */
    private function generateLicenseKey(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $segments = [];

        for ($i = 0; $i < 4; $i++) {
            $segment = '';
            for ($j = 0; $j < 4; $j++) {
                $segment .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $segments[] = $segment;
        }

        return implode('-', $segments);
    }
}

