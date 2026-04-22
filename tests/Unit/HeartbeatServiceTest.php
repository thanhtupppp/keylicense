<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Exceptions\SeatNotFoundException;
use App\Models\Activation;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Models\Product;
use App\Services\HeartbeatService;
use App\States\License\ActiveState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeartbeatServiceTest extends TestCase
{
    use RefreshDatabase;

    private HeartbeatService $service;
    private Product $product;
    private License $license;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HeartbeatService();

        // Create a test product
        $this->product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key' => 'test-api-key-' . uniqid(),
        ]);

        // Create a test license
        $this->license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'floating',
            'max_seats' => 5,
            'status' => new ActiveState(new License()),
        ]);
    }
#[Test]
    public function it_updates_heartbeat_timestamp_for_existing_seat()
    {
        $fingerprintHash = hash('sha256', 'device-123');

        // Create an activation
        $activation = Activation::create([
            'license_id' => $this->license->id,
            'device_fp_hash' => $fingerprintHash,
            'type' => 'floating',
            'activated_at' => now(),
            'is_active' => true,
        ]);

        // Create a floating seat with an old heartbeat
        $oldHeartbeat = Carbon::now()->subMinutes(5);
        $seat = FloatingSeat::create([
            'license_id' => $this->license->id,
            'activation_id' => $activation->id,
            'device_fp_hash' => $fingerprintHash,
            'last_heartbeat_at' => $oldHeartbeat,
        ]);

        // Call heartbeat
        $this->service->heartbeat($this->license, $fingerprintHash);

        // Verify the heartbeat was updated
        $seat->refresh();
        $this->assertTrue($seat->last_heartbeat_at->isAfter($oldHeartbeat));
        $this->assertTrue($seat->last_heartbeat_at->diffInSeconds(now()) < 2);
    }
#[Test]
    public function it_throws_exception_when_seat_not_found()
    {
        $fingerprintHash = hash('sha256', 'non-existent-device');

        $this->expectException(SeatNotFoundException::class);
        $this->expectExceptionMessage('Floating seat not found for this device');

        $this->service->heartbeat($this->license, $fingerprintHash);
    }
#[Test]
    public function it_releases_stale_seats_older_than_10_minutes()
    {
        $fingerprintHash1 = hash('sha256', 'device-1');
        $fingerprintHash2 = hash('sha256', 'device-2');
        $fingerprintHash3 = hash('sha256', 'device-3');

        // Create activations
        $activation1 = Activation::create([
            'license_id' => $this->license->id,
            'device_fp_hash' => $fingerprintHash1,
            'type' => 'floating',
            'activated_at' => now(),
            'is_active' => true,
        ]);

        $activation2 = Activation::create([
            'license_id' => $this->license->id,
            'device_fp_hash' => $fingerprintHash2,
            'type' => 'floating',
            'activated_at' => now(),
            'is_active' => true,
        ]);

        $activation3 = Activation::create([
            'license_id' => $this->license->id,
            'device_fp_hash' => $fingerprintHash3,
            'type' => 'floating',
            'activated_at' => now(),
            'is_active' => true,
        ]);

        // Create floating seats with different heartbeat times
        // Stale seat (11 minutes old)
        $staleSeat = FloatingSeat::create([
            'license_id' => $this->license->id,
            'activation_id' => $activation1->id,
            'device_fp_hash' => $fingerprintHash1,
            'last_heartbeat_at' => Carbon::now()->subMinutes(11),
        ]);

        // Fresh seat (5 minutes old)
        $freshSeat = FloatingSeat::create([
            'license_id' => $this->license->id,
            'activation_id' => $activation2->id,
            'device_fp_hash' => $fingerprintHash2,
            'last_heartbeat_at' => Carbon::now()->subMinutes(5),
        ]);

        // Another stale seat (15 minutes old)
        $anotherStaleSeat = FloatingSeat::create([
            'license_id' => $this->license->id,
            'activation_id' => $activation3->id,
            'device_fp_hash' => $fingerprintHash3,
            'last_heartbeat_at' => Carbon::now()->subMinutes(15),
        ]);

        // Release stale seats
        $releasedCount = $this->service->releaseStaleSeats();

        // Verify 2 stale seats were released
        $this->assertEquals(2, $releasedCount);

        // Verify stale seats are deleted
        $this->assertNull(FloatingSeat::find($staleSeat->id));
        $this->assertNull(FloatingSeat::find($anotherStaleSeat->id));

        // Verify fresh seat still exists
        $this->assertNotNull(FloatingSeat::find($freshSeat->id));
    }
#[Test]
    public function it_releases_seats_exactly_at_10_minute_threshold()
    {
        $fingerprintHash = hash('sha256', 'device-threshold');

        $activation = Activation::create([
            'license_id' => $this->license->id,
            'device_fp_hash' => $fingerprintHash,
            'type' => 'floating',
            'activated_at' => now(),
            'is_active' => true,
        ]);

        // Create a seat exactly 10 minutes old
        $seat = FloatingSeat::create([
            'license_id' => $this->license->id,
            'activation_id' => $activation->id,
            'device_fp_hash' => $fingerprintHash,
            'last_heartbeat_at' => Carbon::now()->subMinutes(10)->subSecond(),
        ]);

        $releasedCount = $this->service->releaseStaleSeats();

        // Verify the seat was released (> 10 minutes)
        $this->assertEquals(1, $releasedCount);
        $this->assertNull(FloatingSeat::find($seat->id));
    }
#[Test]
    public function it_does_not_release_seats_just_under_10_minutes()
    {
        $fingerprintHash = hash('sha256', 'device-fresh');

        $activation = Activation::create([
            'license_id' => $this->license->id,
            'device_fp_hash' => $fingerprintHash,
            'type' => 'floating',
            'activated_at' => now(),
            'is_active' => true,
        ]);

        // Create a seat just under 10 minutes old
        $seat = FloatingSeat::create([
            'license_id' => $this->license->id,
            'activation_id' => $activation->id,
            'device_fp_hash' => $fingerprintHash,
            'last_heartbeat_at' => Carbon::now()->subMinutes(10)->addSecond(),
        ]);

        $releasedCount = $this->service->releaseStaleSeats();

        // Verify no seats were released
        $this->assertEquals(0, $releasedCount);
        $this->assertNotNull(FloatingSeat::find($seat->id));
    }
#[Test]
    public function it_returns_zero_when_no_stale_seats_exist()
    {
        // Don't create any seats
        $releasedCount = $this->service->releaseStaleSeats();

        $this->assertEquals(0, $releasedCount);
    }
}


