<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Exceptions\InvalidTransitionException;
use App\Exceptions\LicenseExpiredException;
use App\Models\Activation;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Models\OfflineTokenJti;
use App\Models\Product;
use App\Services\LicenseService;
use App\States\License\ActiveState;
use App\States\License\ExpiredState;
use App\States\License\InactiveState;
use App\States\License\RevokedState;
use App\States\License\SuspendedState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseServiceTest extends TestCase
{
    use RefreshDatabase;

    private LicenseService $service;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LicenseService();

        // Create a test product
        $this->product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'status' => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key' => 'test-api-key-' . uniqid(),
        ]);
    }
#[Test]
    public function it_can_activate_an_inactive_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
        ]);

        $result = $this->service->activate($license);

        $this->assertInstanceOf(ActiveState::class, $result->status);
    }
#[Test]
    public function it_cannot_activate_an_active_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        $this->expectException(InvalidTransitionException::class);
        $this->service->activate($license);
    }
#[Test]
    public function it_can_expire_an_active_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        $result = $this->service->expire($license);

        $this->assertInstanceOf(ExpiredState::class, $result->status);
    }
#[Test]
    public function it_can_suspend_an_active_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        // Create an activation
        $activation = Activation::create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'device-123'),
            'type' => 'per-device',
            'activated_at' => now(),
            'is_active' => true,
        ]);

        // Create a JTI
        $jti = OfflineTokenJti::create([
            'license_id' => $license->id,
            'jti' => 'test-jti-123',
            'expires_at' => now()->addDay(),
            'is_revoked' => false,
        ]);

        $result = $this->service->suspend($license);

        $this->assertInstanceOf(SuspendedState::class, $result->status);

        // Verify activation is deactivated
        $this->assertFalse($activation->fresh()->is_active);

        // Verify JTI is revoked
        $this->assertTrue($jti->fresh()->is_revoked);
    }
#[Test]
    public function it_can_revoke_an_active_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'floating',
            'max_seats' => 5,
            'status' => new ActiveState(new License()),
        ]);

        // Create an activation
        $activation = Activation::create([
            'license_id' => $license->id,
            'device_fp_hash' => hash('sha256', 'device-123'),
            'type' => 'floating',
            'activated_at' => now(),
            'is_active' => true,
        ]);

        // Create a floating seat
        $seat = FloatingSeat::create([
            'license_id' => $license->id,
            'activation_id' => $activation->id,
            'device_fp_hash' => hash('sha256', 'device-123'),
            'last_heartbeat_at' => now(),
        ]);

        // Create a JTI
        $jti = OfflineTokenJti::create([
            'license_id' => $license->id,
            'jti' => 'test-jti-123',
            'expires_at' => now()->addDay(),
            'is_revoked' => false,
        ]);

        $result = $this->service->revoke($license);

        $this->assertInstanceOf(RevokedState::class, $result->status);

        // Verify activation is deactivated
        $this->assertFalse($activation->fresh()->is_active);

        // Verify JTI is revoked
        $this->assertTrue($jti->fresh()->is_revoked);

        // Verify floating seat is deleted
        $this->assertNull(FloatingSeat::find($seat->id));
    }
#[Test]
    public function it_can_restore_a_suspended_license_if_not_expired()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new SuspendedState(new License()),
            'expiry_date' => Carbon::now()->addDays(30),
        ]);

        $result = $this->service->restore($license);

        $this->assertInstanceOf(ActiveState::class, $result->status);
    }
#[Test]
    public function it_cannot_restore_a_suspended_license_if_expired()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new SuspendedState(new License()),
            'expiry_date' => Carbon::now()->subDays(1),
        ]);

        $this->expectException(LicenseExpiredException::class);
        $this->service->restore($license);
    }
#[Test]
    public function it_can_renew_an_expired_license_and_transitions_to_suspended()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new ExpiredState(new License()),
            'expiry_date' => Carbon::now()->subDays(10),
        ]);

        $newExpiryDate = Carbon::now()->addDays(365);
        $result = $this->service->renew($license, $newExpiryDate);

        $this->assertInstanceOf(SuspendedState::class, $result->status);
        $this->assertTrue($result->expiry_date->isSameDay($newExpiryDate));
    }
#[Test]
    public function it_can_renew_a_suspended_license_and_stays_suspended()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new SuspendedState(new License()),
            'expiry_date' => Carbon::now()->addDays(10),
        ]);

        $newExpiryDate = Carbon::now()->addDays(365);
        $result = $this->service->renew($license, $newExpiryDate);

        $this->assertInstanceOf(SuspendedState::class, $result->status);
        $this->assertTrue($result->expiry_date->isSameDay($newExpiryDate));
    }
#[Test]
    public function it_can_renew_an_active_license_and_stays_active()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
            'expiry_date' => Carbon::now()->addDays(10),
        ]);

        $newExpiryDate = Carbon::now()->addDays(365);
        $result = $this->service->renew($license, $newExpiryDate);

        $this->assertInstanceOf(ActiveState::class, $result->status);
        $this->assertTrue($result->expiry_date->isSameDay($newExpiryDate));
    }
#[Test]
    public function it_can_unrevoke_a_revoked_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new RevokedState(new License()),
        ]);

        $result = $this->service->unrevoke($license);

        $this->assertInstanceOf(InactiveState::class, $result->status);
    }
#[Test]
    public function it_cannot_unrevoke_a_non_revoked_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        $this->expectException(InvalidTransitionException::class);
        $this->service->unrevoke($license);
    }
#[Test]
    public function it_cannot_suspend_an_inactive_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new InactiveState(new License()),
        ]);

        $this->expectException(InvalidTransitionException::class);
        $this->service->suspend($license);
    }
#[Test]
    public function it_cannot_revoke_an_expired_license()
    {
        $license = License::create([
            'product_id' => $this->product->id,
            'key_hash' => hash('sha256', 'TEST-KEY-1234'),
            'key_last4' => '1234',
            'license_model' => 'per-device',
            'status' => new ExpiredState(new License()),
        ]);

        $this->expectException(InvalidTransitionException::class);
        $this->service->revoke($license);
    }
}


