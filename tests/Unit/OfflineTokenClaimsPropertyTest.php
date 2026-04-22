<?php

namespace Tests\Unit;

use App\Models\Activation;
use App\Models\License;
use App\Models\Product;
use App\Services\OfflineTokenService;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Tests\TestCase;

/**
 * Property-based test for offline token claims and TTL correctness
 *
 * **Validates: Requirements 6.1, 6.5**
 *
 */
class OfflineTokenClaimsPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private OfflineTokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new OfflineTokenService();
    }

    /**
     * Property 10: Offline token claims and TTL correctness
     *
     * For any offline_token_ttl_hours = T:
     * - exp - iat = T * 3600
     * - iss = "license-platform"
     * - aud = product.slug
     * - device_fp_hash = SHA-256(fingerprint)
     * - All required claims are present
     *
     * Uses Eris to generate random TTL values (1-168 hours) and verifies
     * that all JWT claims are correct for all generated configurations.
     *
     */
    public function property_offline_token_contains_all_required_claims_with_correct_values(): void
    {
        $this->limitTo(100)->forAll(
            // Random TTL from 1 to 168 hours (7 days)
            Generator\choose(1, 168),
            // Random device fingerprint string
            Generator\suchThat(
                fn($s) => \strlen($s) >= 10 && \strlen($s) <= 255,
                Generator\string()
            )
        )->then(function (int $ttlHours, string $deviceFingerprint) {
            // Create product with the random TTL
            $product = Product::create([
                'name'                    => 'Test Product',
                'slug'                    => 'test-product-' . uniqid(),
                'status'                  => 'active',
                'offline_token_ttl_hours' => $ttlHours,
                'api_key'                 => 'test-api-key-' . uniqid(),
            ]);

            // Create license
            $licenseKeyPlaintext = 'TEST-KEY-' . uniqid();
            $license = License::create([
                'product_id'    => $product->id,
                'key_hash'      => hash('sha256', $licenseKeyPlaintext),
                'key_last4'     => substr($licenseKeyPlaintext, -4),
                'license_model' => 'per-device',
                'status'        => 'active',
                'expiry_date'   => now()->addYear(),
            ]);

            // Create activation
            $deviceFpHash = hash('sha256', $deviceFingerprint);
            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => $deviceFpHash,
                'user_identifier' => null,
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Issue offline token
            $tokenString = $this->tokenService->issue($activation, $product);

            // Parse the token
            $parser = new Parser(new JoseEncoder());
            $token = $parser->parse($tokenString);
            $claims = $token->claims();

            // Verify all required claims are present
            $this->assertTrue(
                $claims->has('iss'),
                'Token must have iss claim'
            );
            $this->assertTrue(
                $claims->has('aud'),
                'Token must have aud claim'
            );
            $this->assertTrue(
                $claims->has('sub'),
                'Token must have sub claim'
            );
            $this->assertTrue(
                $claims->has('jti'),
                'Token must have jti claim'
            );
            $this->assertTrue(
                $claims->has('iat'),
                'Token must have iat claim'
            );
            $this->assertTrue(
                $claims->has('nbf'),
                'Token must have nbf claim'
            );
            $this->assertTrue(
                $claims->has('exp'),
                'Token must have exp claim'
            );
            $this->assertTrue(
                $claims->has('device_fp_hash'),
                'Token must have device_fp_hash claim'
            );
            $this->assertTrue(
                $claims->has('license_model'),
                'Token must have license_model claim'
            );
            $this->assertTrue(
                $claims->has('license_expiry'),
                'Token must have license_expiry claim'
            );

            // Verify iss = "license-platform"
            $this->assertEquals(
                'license-platform',
                $claims->get('iss'),
                'iss claim must equal "license-platform"'
            );

            // Verify aud = product.slug
            $aud = $claims->get('aud');
            $audString = is_array($aud) ? (count($aud) > 0 ? (string) $aud[0] : '') : (string) $aud;
            $this->assertEquals(
                $product->slug,
                $audString,
                'aud claim must equal product slug'
            );

            // Verify sub = SHA-256(license_key_hash)
            $this->assertEquals(
                hash('sha256', $license->key_hash),
                $claims->get('sub'),
                'sub claim must equal SHA-256 of license key_hash'
            );

            // Verify jti is UUID v4 format
            $jti = $claims->get('jti');
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $jti,
                'jti claim must be a valid UUID v4'
            );

            // Verify exp - iat = T * 3600
            $iat = $claims->get('iat')->getTimestamp();
            $exp = $claims->get('exp')->getTimestamp();
            $actualTtlSeconds = $exp - $iat;
            $expectedTtlSeconds = $ttlHours * 3600;

            $this->assertEquals(
                $expectedTtlSeconds,
                $actualTtlSeconds,
                "exp - iat must equal {$ttlHours} * 3600 = {$expectedTtlSeconds} seconds"
            );

            // Verify device_fp_hash = SHA-256(fingerprint)
            $this->assertEquals(
                $deviceFpHash,
                $claims->get('device_fp_hash'),
                'device_fp_hash claim must equal SHA-256 of device fingerprint'
            );

            // Verify license_model is present and correct
            $this->assertEquals(
                'per-device',
                $claims->get('license_model'),
                'license_model claim must match license model'
            );

            // Verify license_expiry is present and correct
            $licenseExpiry = $claims->get('license_expiry');
            $this->assertNotNull(
                $licenseExpiry,
                'license_expiry claim must be present'
            );
            $this->assertStringContainsString(
                'T',
                $licenseExpiry,
                'license_expiry must be in ISO 8601 format'
            );

            // Verify iat and nbf are the same (as per implementation)
            $nbf = $claims->get('nbf')->getTimestamp();
            $this->assertEquals(
                $iat,
                $nbf,
                'nbf claim should equal iat claim'
            );

            // Verify exp is in the future
            $this->assertGreaterThan(
                time(),
                $exp,
                'exp claim must be in the future'
            );
        });
    }

    /**
     * Property 10: Offline token TTL respects product configuration
     *
     * For any two products with different TTL configurations,
     * tokens issued for each product must have the correct TTL.
     *
     */
    public function property_offline_token_ttl_respects_product_configuration(): void
    {
        $this->limitTo(50)->forAll(
            // First product TTL (1-168 hours)
            Generator\choose(1, 168),
            // Second product TTL (1-168 hours)
            Generator\choose(1, 168)
        )->then(function (int $ttl1, int $ttl2) {
            // Create two products with different TTLs
            $product1 = Product::create([
                'name'                    => 'Product 1',
                'slug'                    => 'product-1-' . uniqid(),
                'status'                  => 'active',
                'offline_token_ttl_hours' => $ttl1,
                'api_key'                 => 'api-key-1-' . uniqid(),
            ]);

            $product2 = Product::create([
                'name'                    => 'Product 2',
                'slug'                    => 'product-2-' . uniqid(),
                'status'                  => 'active',
                'offline_token_ttl_hours' => $ttl2,
                'api_key'                 => 'api-key-2-' . uniqid(),
            ]);

            // Create licenses and activations for both products
            $license1 = License::create([
                'product_id'    => $product1->id,
                'key_hash'      => hash('sha256', 'KEY1-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'per-device',
                'status'        => 'active',
            ]);

            $activation1 = Activation::create([
                'license_id'      => $license1->id,
                'device_fp_hash'  => hash('sha256', 'device1'),
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            $license2 = License::create([
                'product_id'    => $product2->id,
                'key_hash'      => hash('sha256', 'KEY2-' . uniqid()),
                'key_last4'     => '5678',
                'license_model' => 'per-device',
                'status'        => 'active',
            ]);

            $activation2 = Activation::create([
                'license_id'      => $license2->id,
                'device_fp_hash'  => hash('sha256', 'device2'),
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Issue tokens for both products
            $token1String = $this->tokenService->issue($activation1, $product1);
            $token2String = $this->tokenService->issue($activation2, $product2);

            // Parse tokens
            $parser = new Parser(new JoseEncoder());
            $token1 = $parser->parse($token1String);
            $token2 = $parser->parse($token2String);

            // Verify TTLs
            $iat1 = $token1->claims()->get('iat')->getTimestamp();
            $exp1 = $token1->claims()->get('exp')->getTimestamp();
            $actualTtl1 = $exp1 - $iat1;

            $iat2 = $token2->claims()->get('iat')->getTimestamp();
            $exp2 = $token2->claims()->get('exp')->getTimestamp();
            $actualTtl2 = $exp2 - $iat2;

            $this->assertEquals(
                $ttl1 * 3600,
                $actualTtl1,
                "Token 1 TTL must equal product 1 TTL ({$ttl1} hours)"
            );

            $this->assertEquals(
                $ttl2 * 3600,
                $actualTtl2,
                "Token 2 TTL must equal product 2 TTL ({$ttl2} hours)"
            );

            // If TTLs are different, verify tokens have different expiration times
            if ($ttl1 !== $ttl2) {
                $this->assertNotEquals(
                    $actualTtl1,
                    $actualTtl2,
                    'Tokens from products with different TTLs must have different expiration times'
                );
            }
        });
    }

    /**
     * Property 10: Offline token claims are deterministic for same input
     *
     * For any activation, issuing multiple tokens should produce
     * tokens with the same claims (except jti and timestamps).
     *
     */
    public function property_offline_token_claims_are_deterministic_for_same_activation(): void
    {
        $this->limitTo(50)->forAll(
            // Random TTL
            Generator\choose(1, 168)
        )->then(function (int $ttlHours) {
            // Create product, license, and activation
            $product = Product::create([
                'name'                    => 'Test Product',
                'slug'                    => 'test-product-' . uniqid(),
                'status'                  => 'active',
                'offline_token_ttl_hours' => $ttlHours,
                'api_key'                 => 'test-api-key-' . uniqid(),
            ]);

            $license = License::create([
                'product_id'    => $product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'per-user',
                'status'        => 'active',
                'expiry_date'   => now()->addMonths(6),
            ]);

            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => null,
                'user_identifier' => 'user@example.com',
                'type'            => 'per-user',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Issue two tokens for the same activation
            $token1String = $this->tokenService->issue($activation, $product);
            sleep(1); // Ensure different timestamps
            $token2String = $this->tokenService->issue($activation, $product);

            // Parse tokens
            $parser = new Parser(new JoseEncoder());
            $token1 = $parser->parse($token1String);
            $token2 = $parser->parse($token2String);

            $claims1 = $token1->claims();
            $claims2 = $token2->claims();

            // Verify deterministic claims are the same
            $this->assertEquals(
                $claims1->get('iss'),
                $claims2->get('iss'),
                'iss claim should be the same for same activation'
            );

            $aud1 = $claims1->get('aud');
            $aud1String = is_array($aud1) ? (count($aud1) > 0 ? (string) $aud1[0] : '') : (string) $aud1;
            $aud2 = $claims2->get('aud');
            $aud2String = is_array($aud2) ? (count($aud2) > 0 ? (string) $aud2[0] : '') : (string) $aud2;

            $this->assertEquals(
                $aud1String,
                $aud2String,
                'aud claim should be the same for same activation'
            );

            $this->assertEquals(
                $claims1->get('sub'),
                $claims2->get('sub'),
                'sub claim should be the same for same activation'
            );

            $this->assertEquals(
                $claims1->get('license_model'),
                $claims2->get('license_model'),
                'license_model claim should be the same for same activation'
            );

            // Verify jti is different (unique token ID)
            $this->assertNotEquals(
                $claims1->get('jti'),
                $claims2->get('jti'),
                'jti claim should be unique for each token'
            );

            // Verify TTL is consistent
            $ttl1 = $claims1->get('exp')->getTimestamp() - $claims1->get('iat')->getTimestamp();
            $ttl2 = $claims2->get('exp')->getTimestamp() - $claims2->get('iat')->getTimestamp();

            $this->assertEquals(
                $ttl1,
                $ttl2,
                'TTL should be the same for tokens from same product'
            );

            $this->assertEquals(
                $ttlHours * 3600,
                $ttl1,
                'TTL should match product configuration'
            );
        });
    }
}

