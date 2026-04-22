<?php

namespace Tests\Unit;

use App\Exceptions\InvalidTransitionException;
use App\Models\License;
use App\Models\Product;
use App\Services\LicenseService;
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
 * Property-based test for state machine transitions
 *
 * **Validates: Requirements 3b.1, 3.1**
 *
 */
class StateMachinePropertyTest extends TestCase
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
            'slug'                    => 'test-product-sm-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-sm-' . uniqid(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * All (state-name => state-class) pairs used in the state machine.
     */
    private function allStateClasses(): array
    {
        return [
            'inactive'  => InactiveState::class,
            'active'    => ActiveState::class,
            'expired'   => ExpiredState::class,
            'suspended' => SuspendedState::class,
            'revoked'   => RevokedState::class,
        ];
    }

    /**
     * Valid transitions per state, as defined in the state machine table
     * (design.md + requirements.md).
     *
     * From `inactive`  : activate, revoke
     * From `active`    : expire, suspend, revoke, renew  (req 3.6 allows renew from active)
     * From `expired`   : renew
     * From `suspended` : restore, revoke, renew
     * From `revoked`   : un-revoke
     *
     * Note: Property 5 in design.md lists from `active` as only expire/suspend/revoke,
     * but requirement 3.6 explicitly allows renew from `active` (stays active, updates
     * expiry_date). The implementation correctly supports this, so we include it here.
     */
    private function validTransitions(): array
    {
        return [
            'inactive'  => ['activate', 'revoke'],
            'active'    => ['expire', 'suspend', 'revoke', 'renew'],
            'expired'   => ['renew'],
            'suspended' => ['restore', 'revoke', 'renew'],
            'revoked'   => ['unrevoke'],
        ];
    }

    /**
     * All action names used in the state machine.
     */
    private function allActions(): array
    {
        return ['activate', 'expire', 'suspend', 'revoke', 'restore', 'renew', 'unrevoke'];
    }

    /**
     * Create a license in the given state.
     * For restore tests we need a future (or NULL) expiry_date so the restore
     * hook does not throw LicenseExpiredException.
     */
    private function createLicense(string $stateClass, ?Carbon $expiryDate = null): License
    {
        return License::create([
            'product_id'    => $this->product->id,
            'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
            'key_last4'     => '1234',
            'license_model' => 'per-device',
            'status'        => new $stateClass(new License()),
            'expiry_date'   => $expiryDate,
        ]);
    }

    /**
     * Invoke the LicenseService method that corresponds to the given action.
     * Returns the resulting license on success, or re-throws any exception.
     *
     */
    private function invokeAction(License $license, string $action, Carbon $renewDate): License
    {
        return match ($action) {
            'activate'  => $this->service->activate($license),
            'expire'    => $this->service->expire($license),
            'suspend'   => $this->service->suspend($license),
            'revoke'    => $this->service->revoke($license),
            'restore'   => $this->service->restore($license),
            'renew'     => $this->service->renew($license, $renewDate),
            'unrevoke'  => $this->service->unrevoke($license),
            default     => throw new \InvalidArgumentException("Unknown action: {$action}"),
        };
    }

    // -------------------------------------------------------------------------
    // Property 5a — valid transitions succeed
    // -------------------------------------------------------------------------

    /**
     * Property 5: Valid transitions succeed
     *
     * For every (state, action) pair that is listed in the valid transition
     * table, calling the corresponding LicenseService method SHALL NOT throw
     * InvalidTransitionException.
     *
     * Uses Eris to generate random future expiry dates and customer names so
     * that the test exercises a variety of license configurations.
     *
     */
    public function property_valid_transitions_do_not_throw_invalid_transition_exception(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of days in the future for expiry (1–3650)
            Generator\choose(1, 3650),
            // Random customer name string
            Generator\suchThat(
                fn($s) => \strlen($s) >= 1 && \strlen($s) <= 100,
                Generator\string()
            )
        )->then(function (int $daysAhead, string $customerName) {
            $futureExpiry = Carbon::now()->addDays($daysAhead);
            $validTransitions = $this->validTransitions();

            foreach ($validTransitions as $stateName => $actions) {
                $stateClasses = $this->allStateClasses();
                $stateClass   = $stateClasses[$stateName];

                foreach ($actions as $action) {
                    // For restore, we need a future expiry_date so the hook
                    // does not throw LicenseExpiredException.
                    $expiryDate = ($action === 'restore') ? $futureExpiry : null;

                    $license = $this->createLicense($stateClass, $expiryDate);

                    $threw = false;
                    try {
                        $this->invokeAction($license, $action, $futureExpiry);
                    } catch (InvalidTransitionException $e) {
                        $threw = true;
                    } catch (\Throwable $e) {
                        // Other exceptions (e.g. LicenseExpiredException) are
                        // not InvalidTransitionException — they are acceptable
                        // for this property test.
                    }

                    $this->assertFalse(
                        $threw,
                        "Valid transition '{$action}' from state '{$stateName}' " .
                            "should NOT throw InvalidTransitionException, but it did."
                    );
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // Property 5b — invalid transitions are rejected
    // -------------------------------------------------------------------------

    /**
     * Property 5: Invalid transitions are rejected
     *
     * For every (state, action) pair that is NOT listed in the valid transition
     * table, calling the corresponding LicenseService method SHALL throw
     * InvalidTransitionException.
     *
     * Uses Eris to generate random future expiry dates so the test exercises
     * a variety of license configurations.
     *
     */
    public function property_invalid_transitions_throw_invalid_transition_exception(): void
    {
        $this->limitTo(100)->forAll(
            // Random number of days in the future for expiry (1–3650)
            Generator\choose(1, 3650)
        )->then(function (int $daysAhead) {
            $futureExpiry     = Carbon::now()->addDays($daysAhead);
            $validTransitions = $this->validTransitions();
            $allActions       = $this->allActions();
            $stateClasses     = $this->allStateClasses();

            foreach ($stateClasses as $stateName => $stateClass) {
                $validForState   = $validTransitions[$stateName];
                $invalidActions  = array_diff($allActions, $validForState);

                foreach ($invalidActions as $action) {
                    $license = $this->createLicense($stateClass, $futureExpiry);

                    $threw = false;
                    try {
                        $this->invokeAction($license, $action, $futureExpiry);
                    } catch (InvalidTransitionException $e) {
                        $threw = true;
                    } catch (\Throwable $e) {
                        // Any other exception is not InvalidTransitionException;
                        // the transition was not properly rejected.
                    }

                    $this->assertTrue(
                        $threw,
                        "Invalid transition '{$action}' from state '{$stateName}' " .
                            "should throw InvalidTransitionException, but it did not."
                    );
                }
            }
        });
    }
}

