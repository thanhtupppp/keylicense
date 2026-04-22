<?php

namespace Tests\Unit;

use App\Exceptions\InvalidTokenException;
use App\Models\Activation;
use App\Models\License;
use App\Models\OfflineTokenJti;
use App\Models\Product;
use App\Services\OfflineTokenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private OfflineTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OfflineTokenService();
    }

    /**
     */
    public function it_can_issue_offline_token()
    {
        $product = Product::factory()->create([
            'offline_token_ttl_hours' => 24,
        ]);

        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'per-device',
                'expiry_date' => Carbon::now()->addYear(),
            ]);

        $activation = Activation::factory()
            ->for($license)
            ->create([
                'device_fp_hash' => hash('sha256', 'device-123'),
                'type' => 'per-device',
            ]);

        $token = $this->service->issue($activation, $product);

        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token); // JWT has dots

        // JTI should be saved
        $jtiCount = OfflineTokenJti::where('license_id', $license->id)->count();
        $this->assertEquals(1, $jtiCount);
    }

    /**
     */
    public function it_can_verify_valid_offline_token()
    {
        $product = Product::factory()->create([
            'offline_token_ttl_hours' => 24,
        ]);

        $license = License::factory()
            ->for($product)
            ->create([
                'license_model' => 'per-device',
                'expiry_date' => Carbon::now()->addYear(),
            ]);

        $activation = Activation::factory()
            ->for($license)
            ->create([
                'device_fp_hash' => hash('sha256', 'device-123'),
                'type' => 'per-device',
            ]);

        $token = $this->service->issue($activation, $product);
        $claims = $this->service->verify($token, $product);

        $this->assertIsArray($claims);
        $this->assertEquals('license-platform', $claims['iss']);
        $this->assertEquals($product->slug, $claims['aud']);
        $this->assertNotEmpty($claims['jti']);
        $this->assertNotEmpty($claims['device_fp_hash']);
        $this->assertEquals('per-device', $claims['license_model']);
    }

    /**
     */
    public function it_rejects_revoked_token()
    {
        $product = Product::factory()->create([
            'offline_token_ttl_hours' => 24,
        ]);

        $license = License::factory()
            ->for($product)
            ->create();

        $activation = Activation::factory()
            ->for($license)
            ->create();

        $token = $this->service->issue($activation, $product);

        // Revoke the token
        $this->service->revokeAllForLicense($license);

        // Verification should fail
        $this->expectException(InvalidTokenException::class);
        $this->service->verify($token, $product);
    }

    /**
     */
    public function it_rejects_expired_token()
    {
        $product = Product::factory()->create([
            'offline_token_ttl_hours' => 24,
        ]);

        $license = License::factory()
            ->for($product)
            ->create();

        $activation = Activation::factory()
            ->for($license)
            ->create();

        $token = $this->service->issue($activation, $product);

        // Manually expire the JTI record
        OfflineTokenJti::where('license_id', $license->id)
            ->update(['expires_at' => Carbon::now()->subHour()]);

        // Verification should fail
        $this->expectException(InvalidTokenException::class);
        $this->service->verify($token, $product);
    }

    /**
     */
    public function it_can_revoke_all_tokens_for_license()
    {
        $product = Product::factory()->create();
        $license = License::factory()
            ->for($product)
            ->create();

        $activation1 = Activation::factory()
            ->for($license)
            ->create();

        $activation2 = Activation::factory()
            ->for($license)
            ->create();

        // Issue tokens
        $this->service->issue($activation1, $product);
        $this->service->issue($activation2, $product);

        // Revoke all
        $this->service->revokeAllForLicense($license);

        // All JTIs should be revoked
        $revokedCount = OfflineTokenJti::where('license_id', $license->id)
            ->where('is_revoked', true)
            ->count();

        $this->assertEquals(2, $revokedCount);
    }

    /**
     */
    public function it_includes_all_required_claims()
    {
        $product = Product::factory()->create([
            'offline_token_ttl_hours' => 24,
        ]);

        $license = License::factory()
            ->for($product)
            ->create([
                'expiry_date' => Carbon::now()->addYear(),
            ]);

        $activation = Activation::factory()
            ->for($license)
            ->create([
                'device_fp_hash' => hash('sha256', 'device-123'),
            ]);

        $token = $this->service->issue($activation, $product);
        $claims = $this->service->verify($token, $product);

        // Verify all required claims are present
        $this->assertArrayHasKey('iss', $claims);
        $this->assertArrayHasKey('aud', $claims);
        $this->assertArrayHasKey('sub', $claims);
        $this->assertArrayHasKey('jti', $claims);
        $this->assertArrayHasKey('iat', $claims);
        $this->assertArrayHasKey('nbf', $claims);
        $this->assertArrayHasKey('exp', $claims);
        $this->assertArrayHasKey('device_fp_hash', $claims);
        $this->assertArrayHasKey('license_model', $claims);
        $this->assertArrayHasKey('license_expiry', $claims);
    }

    /**
     */
    public function it_calculates_ttl_correctly()
    {
        $product = Product::factory()->create([
            'offline_token_ttl_hours' => 24,
        ]);

        $license = License::factory()
            ->for($product)
            ->create();

        $activation = Activation::factory()
            ->for($license)
            ->create();

        $token = $this->service->issue($activation, $product);
        $claims = $this->service->verify($token, $product);

        // exp - iat should equal 24 * 3600 seconds
        $ttl = $claims['exp'] - $claims['iat'];
        $this->assertEquals(24 * 3600, $ttl);
    }
}

