<?php

namespace Tests\Unit;

use App\Models\Activation;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Models\Product;
use App\Services\HeartbeatService;
use App\States\License\ActiveState;
use Carbon\Carbon;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based test for heartbeat timeout and stale seat release
 *
 * **Validates: Requirements 7.3**
 *
 * Property 12: Heartbeat timeout releases stale seats
 *
 * For any FloatingSeat with `last_heartbeat_at > 10 minutes ago`:
 * - After `releaseStaleSeats()` runs, that seat SHALL be deleted
 * - The available seat count SHALL increase by 1 for each released seat
 * - Seats with `last_heartbeat_at <= 10 minutes ago` SHALL remain unchanged
 *
 */
class HeartbeatTimeoutPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private HeartbeatService $service;
    private Product $product;
    private License $license;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new HeartbeatService();

        $this->product = Product::create([
            'name'                    => 'Test Product',
            'slug'                    => 'test-product-heartbeat-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-heartbeat-' . uniqid(),
        ]);

        $this->license = License::create([
            'product_id'    => $this->product->id,
            'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
            'key_last4'     => '1234',
            'license_model' => 'floating',
            'status'        => new ActiveState(new License()),
            'max_seats'     => 100,
            'expiry_date'   => null,
        ]);
    }

    /**
     * Property 12a: All seats with last_heartbeat_at > 10 minutes ago are released
     *
     */
    public function property_stale_seats_are_released(): void
    {
        $this->limitTo(100)->forAll(
            Generator\choose(1, 20),
            Generator\choose(0, 20)
        )->then(function (int $staleCount, int $freshCount) {
            FloatingSeat::query()->delete();
            Activation::query()->delete();

            $staleSeats = [];
            for ($i = 0; $i < $staleCount; $i++) {
                $fingerprintHash = hash('sha256', "stale-device-{$i}-" . uniqid());

                $activation = Activation::create([
                    'license_id'     => $this->license->id,
                    'device_fp_hash' => $fingerprintHash,
                    'type'           => 'floating',
                    'activated_at'   => now(),
                    'is_active'      => true,
                ]);

                $minutesOld = rand(11, 120);
                $seat = FloatingSeat::create([
                    'license_id'        => $this->license->id,
                    'activation_id'     => $activation->id,
                    'device_fp_hash'    => $fingerprintHash,
                    'last_heartbeat_at' => Carbon::now()->subMinutes($minutesOld),
                ]);

                $staleSeats[] = $seat->id;
            }

            $freshSeats = [];
            for ($i = 0; $i < $freshCount; $i++) {
                $fingerprintHash = hash('sha256', "fresh-device-{$i}-" . uniqid());

                $activation = Activation::create([
                    'license_id'     => $this->license->id,
                    'device_fp_hash' => $fingerprintHash,
                    'type'           => 'floating',
                    'activated_at'   => now(),
                    'is_active'      => true,
                ]);

                $minutesOld = rand(0, 10);
                $seat = FloatingSeat::create([
                    'license_id'        => $this->license->id,
                    'activation_id'     => $activation->id,
                    'device_fp_hash'    => $fingerprintHash,
                    'last_heartbeat_at' => Carbon::now()->subMinutes($minutesOld),
                ]);

                $freshSeats[] = $seat->id;
            }

            $releasedCount = $this->service->releaseStaleSeats();

            $this->assertSame($staleCount, $releasedCount);

            foreach ($staleSeats as $seatId) {
                $this->assertNull(FloatingSeat::find($seatId));
            }

            foreach ($freshSeats as $seatId) {
                $this->assertNotNull(FloatingSeat::find($seatId));
            }
        });
    }
}

