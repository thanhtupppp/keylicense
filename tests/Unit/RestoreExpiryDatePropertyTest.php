<?php

namespace Tests\Unit;

use App\Exceptions\InvalidTransitionException;
use App\Exceptions\LicenseExpiredException;
use App\Models\License;
use App\Models\Product;
use App\Services\LicenseService;
use App\States\License\SuspendedState;
use Carbon\Carbon;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based test for restore expiry date check
 *
 * **Validates: Requirements 3b.7, 3.4**
 *
 */
class RestoreExpiryDatePropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private LicenseService $service;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LicenseService();

        $this->product = Product::create([
            'name'                    => 'Test Product',
            'slug'                    => 'test-product-restore-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-restore-' . uniqid(),
        ]);
    }

    /**
     * Create a suspended license with the given expiry date.
     */
    private function createSuspendedLicense(?Carbon $expiryDate = null): License
    {
        return License::create([
            'product_id'    => $this->product->id,
            'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
            'key_last4'     => '1234',
            'license_model' => 'per-device',
            'status'        => new SuspendedState(new License()),
            'expiry_date'   => $expiryDate,
        ]);
    }

    /**
     * Property 6: Restore checks expiry date
     *
     * For any suspended license:
     * - If expiry_date is NULL: restore should succeed → active
     * - If expiry_date is in the future: restore should succeed → active
     * - If expiry_date is in the past: restore should be rejected with LICENSE_EXPIRED
     *
     * Uses Eris to generate various expiry date scenarios:
     * - NULL (no expiry)
     * - Past dates (1–3650 days ago)
     * - Future dates (1–3650 days ahead)
     *
     */
    public function property_restore_with_null_expiry_date_succeeds(): void
    {
        $this->limitTo(100)->forAll(
            // Generate random customer names to vary the test
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 100,
                Generator\string()
            )
        )->then(function (string $customerName) {
            // Create a suspended license with NULL expiry_date (vĩnh viễn)
            $license = $this->createSuspendedLicense(null);
            $license->customer_name = $customerName;
            $license->save();

            // Restore should succeed
            $restored = $this->service->restore($license);

            // Verify the license is now active
            $this->assertTrue(
                $restored->status instanceof \App\States\License\ActiveState,
                'Restore with NULL expiry_date should transition to active state'
            );

            // Verify the license is persisted correctly
            $this->assertDatabaseHas('licenses', [
                'id'     => $restored->id,
                'status' => 'active',
            ]);
        });
    }

    /**
     * Property 6: Restore with future expiry date succeeds
     *
     * For any suspended license with expiry_date in the future,
     * restore should succeed and transition to active.
     *
     */
    public function property_restore_with_future_expiry_date_succeeds(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of days in the future (1–3650)
            Generator\choose(1, 3650),
            // Random customer names
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 100,
                Generator\string()
            )
        )->then(function (int $daysAhead, string $customerName) {
            $futureExpiry = Carbon::now()->addDays($daysAhead);

            // Create a suspended license with future expiry_date
            $license = $this->createSuspendedLicense($futureExpiry);
            $license->customer_name = $customerName;
            $license->save();

            // Restore should succeed
            $restored = $this->service->restore($license);

            // Verify the license is now active
            $this->assertTrue(
                $restored->status instanceof \App\States\License\ActiveState,
                "Restore with future expiry_date ({$futureExpiry}) should transition to active state"
            );

            // Verify the license is persisted correctly
            $this->assertDatabaseHas('licenses', [
                'id'     => $restored->id,
                'status' => 'active',
            ]);
        });
    }

    /**
     * Property 6: Restore with past expiry date is rejected
     *
     * For any suspended license with expiry_date in the past,
     * restore should be rejected with LicenseExpiredException.
     *
     */
    public function property_restore_with_past_expiry_date_is_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of days in the past (1–3650)
            Generator\choose(1, 3650),
            // Random customer names
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 100,
                Generator\string()
            )
        )->then(function (int $daysPast, string $customerName) {
            $pastExpiry = Carbon::now()->subDays($daysPast);

            // Create a suspended license with past expiry_date
            $license = $this->createSuspendedLicense($pastExpiry);
            $license->customer_name = $customerName;
            $license->save();

            // Restore should throw LicenseExpiredException
            $threw = false;
            $exceptionThrown = null;

            try {
                $this->service->restore($license);
            } catch (LicenseExpiredException $e) {
                $threw = true;
                $exceptionThrown = $e;
            } catch (\Throwable $e) {
                // Any other exception is a failure
                $this->fail(
                    "Restore with past expiry_date ({$pastExpiry}) should throw " .
                        "LicenseExpiredException, but threw " . get_class($e) . ": " . $e->getMessage()
                );
            }

            $this->assertTrue(
                $threw,
                "Restore with past expiry_date ({$pastExpiry}) should throw LicenseExpiredException"
            );

            // Verify the license is still suspended (not changed)
            $license->refresh();
            $this->assertTrue(
                $license->status instanceof SuspendedState,
                'License should remain suspended after failed restore'
            );
        });
    }

    /**
     * Property 6: Restore only works from suspended state
     *
     * Verify that restore can only be called on suspended licenses.
     * This is a sanity check to ensure the test setup is correct.
     *
     */
    public function property_restore_only_works_from_suspended_state(): void
    {
        $this->limitTo(50)->forAll(
            // Random number of days in the future for expiry
            Generator\choose(1, 3650)
        )->then(function (int $daysAhead) {
            $futureExpiry = Carbon::now()->addDays($daysAhead);

            // Create a suspended license
            $license = $this->createSuspendedLicense($futureExpiry);

            // Verify it's in suspended state
            $this->assertTrue(
                $license->status instanceof SuspendedState,
                'Test setup: license should be in suspended state'
            );

            // Verify canRestore returns true
            $this->assertTrue(
                $license->status->canRestore(),
                'Suspended state should allow restore'
            );
        });
    }
}

