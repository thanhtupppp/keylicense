<?php

namespace Tests\Unit;

use App\Contracts\AuditLoggerInterface;
use App\Exceptions\SeatsExhaustedException;
use App\Models\Activation;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Models\Product;
use App\Services\ActivationService;
use App\States\License\ActiveState;
use App\States\License\InactiveState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivationService $service;
    private AuditLoggerInterface $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the audit logger
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->service = new ActivationService($this->auditLogger);
    }

    /**
     */
    public function it_can_activate_per_device_license()
    {
        $product = Product::factory()->create();
        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'per-device',
                'status' => new InactiveState(new License()),
            ]);

        $fingerprint = 'device-fingerprint-123';
        $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');

        $this->assertNotNull($activation);
        $this->assertEquals($license->id, $activation->license_id);
        $this->assertEquals(hash('sha256', $fingerprint), $activation->device_fp_hash);
        $this->assertEquals('per-device', $activation->type);
        $this->assertTrue($activation->is_active);

        // License should be activated
        $license->refresh();
        $this->assertInstanceOf(ActiveState::class, $license->status);
    }

    /**
     */
    public function it_can_activate_per_user_license()
    {
        $product = Product::factory()->create();
        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'per-user',
                'status' => new InactiveState(new License()),
            ]);

        $userIdentifier = 'user@example.com';
        $activation = $this->service->activate($license, 'fingerprint', $userIdentifier, '127.0.0.1');

        $this->assertNotNull($activation);
        $this->assertEquals($license->id, $activation->license_id);
        $this->assertEquals($userIdentifier, $activation->user_identifier);
        $this->assertEquals('per-user', $activation->type);
        $this->assertTrue($activation->is_active);
    }

    /**
     */
    public function it_can_activate_floating_license()
    {
        $product = Product::factory()->create();
        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'floating',
                'max_seats' => 5,
                'status' => new InactiveState(new License()),
            ]);

        $fingerprint = 'device-fingerprint-123';
        $activation = $this->service->activate($license, $fingerprint, null, '127.0.0.1');

        $this->assertNotNull($activation);
        $this->assertEquals('floating', $activation->type);

        // Floating seat should be created
        $seat = FloatingSeat::where('license_id', $license->id)
            ->where('device_fp_hash', hash('sha256', $fingerprint))
            ->first();

        $this->assertNotNull($seat);
        $this->assertEquals($activation->id, $seat->activation_id);
    }

    /**
     */
    public function it_throws_seats_exhausted_for_floating_license()
    {
        $product = Product::factory()->create();
        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'floating',
                'max_seats' => 2,
                'status' => new ActiveState(new License()),
            ]);

        // Create 2 activations to exhaust seats
        for ($i = 0; $i < 2; $i++) {
            $activation = Activation::factory()
                ->for($license)
                ->create(['type' => 'floating']);

            FloatingSeat::create([
                'license_id' => $license->id,
                'activation_id' => $activation->id,
                'device_fp_hash' => hash('sha256', "device-$i"),
                'last_heartbeat_at' => Carbon::now(),
            ]);
        }

        // Third activation should fail
        $this->expectException(SeatsExhaustedException::class);
        $this->service->activate($license, 'device-3', null, '127.0.0.1');
    }

    /**
     */
    public function it_handles_per_device_activation_idempotently()
    {
        $product = Product::factory()->create();
        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'per-device',
                'status' => new InactiveState(new License()),
            ]);

        $fingerprint = 'device-fingerprint-123';

        // First activation
        $activation1 = $this->service->activate($license, $fingerprint, null, '127.0.0.1');

        // Second activation with same fingerprint
        $activation2 = $this->service->activate($license, $fingerprint, null, '127.0.0.1');

        // Should return the same activation
        $this->assertEquals($activation1->id, $activation2->id);

        // Only one activation should exist
        $count = Activation::where('license_id', $license->id)->count();
        $this->assertEquals(1, $count);
    }

    /**
     */
    public function it_can_deactivate_per_device_license()
    {
        $product = Product::factory()->create();
        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'per-device',
                'status' => new ActiveState(new License()),
            ]);

        $fingerprint = 'device-fingerprint-123';
        $fpHash = hash('sha256', $fingerprint);

        $activation = Activation::factory()
            ->for($license)
            ->create([
                'device_fp_hash' => $fpHash,
                'type' => 'per-device',
                'is_active' => true,
            ]);

        $result = $this->service->deactivate($license, $fingerprint);

        $this->assertTrue($result);

        $activation->refresh();
        $this->assertFalse($activation->is_active);

        $license->refresh();
        $this->assertInstanceOf(InactiveState::class, $license->status);
    }

    /**
     */
    public function it_can_deactivate_floating_license()
    {
        $product = Product::factory()->create();
        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'floating',
                'status' => new ActiveState(new License()),
            ]);

        $fingerprint = 'device-fingerprint-123';
        $fpHash = hash('sha256', $fingerprint);

        $activation = Activation::factory()
            ->for($license)
            ->create(['type' => 'floating']);

        FloatingSeat::create([
            'license_id' => $license->id,
            'activation_id' => $activation->id,
            'device_fp_hash' => $fpHash,
            'last_heartbeat_at' => Carbon::now(),
        ]);

        $result = $this->service->deactivate($license, $fingerprint);

        $this->assertTrue($result);

        // Floating seat should be deleted
        $seat = FloatingSeat::where('license_id', $license->id)
            ->where('device_fp_hash', $fpHash)
            ->first();

        $this->assertNull($seat);
    }
}

