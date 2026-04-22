<?php

namespace Tests\Unit;

use App\Exceptions\InvalidTokenException;
use App\Models\Activation;
use App\Models\License;
use App\Models\Product;
use App\Services\OfflineTokenService;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HmacSha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Tests\TestCase;

/**
 * Property-based test for offline token signature verification
 *
 * **Validates: Requirements 6.3, 6.4**
 *
 */
class TokenSignatureVerificationPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private OfflineTokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new OfflineTokenService();
    }

    /**
     * Property 11: Token with tampered signature is rejected
     *
     * For any valid token, if the signature is modified,
     * verification must fail with INVALID_TOKEN.
     *
     */
    public function property_token_with_tampered_signature_is_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Random TTL from 1 to 168 hours
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
                'license_model' => 'per-device',
                'status'        => 'active',
                'expiry_date'   => now()->addYear(),
            ]);

            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => hash('sha256', 'device-fingerprint'),
                'user_identifier' => null,
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Issue a valid token
            $validTokenString = $this->tokenService->issue($activation, $product);

            // Tamper with the signature by modifying the last character
            $parts = explode('.', $validTokenString);
            $this->assertCount(3, $parts, 'JWT must have 3 parts');

            // Modify the signature part
            $tamperedSignature = $parts[2];
            $lastChar = substr($tamperedSignature, -1);
            $newLastChar = ($lastChar === 'A') ? 'B' : 'A';
            $tamperedSignature = substr($tamperedSignature, 0, -1) . $newLastChar;
            $parts[2] = $tamperedSignature;

            $tamperedTokenString = implode('.', $parts);

            // Verify that the tampered token is rejected
            $this->expectException(InvalidTokenException::class);
            $this->expectExceptionMessage('Invalid token signature');

            $this->tokenService->verify($tamperedTokenString, $product);
        });
    }

    /**
     * Property 11: Token with wrong algorithm (not RS256) is rejected
     *
     * For any token signed with an algorithm other than RS256,
     * verification must fail with INVALID_TOKEN.
     *
     */
    public function property_token_with_wrong_algorithm_is_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Random TTL from 1 to 168 hours
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
                'license_model' => 'per-device',
                'status'        => 'active',
                'expiry_date'   => now()->addYear(),
            ]);

            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => hash('sha256', 'device-fingerprint'),
                'user_identifier' => null,
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Create a token with HS256 (HMAC) instead of RS256
            $now = \DateTimeImmutable::createFromMutable(now());
            $ttlSeconds = $ttlHours * 3600;
            $expiresAt = $now->modify("+{$ttlSeconds} seconds");

            $config = Configuration::forSymmetricSigner(
                new HmacSha256(),
                InMemory::plainText('secret-key-for-hmac')
            );

            $token = $config->builder()
                ->issuedBy(config('jwt.issuer'))
                ->permittedFor($product->slug)
                ->identifiedBy(\Illuminate\Support\Str::uuid()->toString())
                ->issuedAt($now)
                ->canOnlyBeUsedAfter($now)
                ->expiresAt($expiresAt)
                ->relatedTo(hash('sha256', $license->key_hash))
                ->withClaim('device_fp_hash', $activation->device_fp_hash)
                ->withClaim('license_model', $license->license_model)
                ->withClaim('license_expiry', $license->expiry_date?->format('c') ?? null)
                ->getToken($config->signer(), $config->signingKey());

            $wrongAlgTokenString = $token->toString();

            // Verify that the token with wrong algorithm is rejected
            $this->expectException(InvalidTokenException::class);

            $this->tokenService->verify($wrongAlgTokenString, $product);
        });
    }

    /**
     * Property 11: Token with nbf - iat > 300 seconds is rejected
     *
     * For any token where nbf is more than 300 seconds in the future
     * relative to iat, verification must fail with INVALID_TOKEN.
     *
     */
    public function property_token_with_excessive_nbf_skew_is_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Random TTL from 1 to 168 hours
            Generator\choose(1, 168),
            // Random nbf skew from 301 to 600 seconds (> 300 seconds but not too far in future)
            Generator\choose(301, 600)
        )->then(function (int $ttlHours, int $nbfSkewSeconds) {
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
                'license_model' => 'per-device',
                'status'        => 'active',
                'expiry_date'   => now()->addYear(),
            ]);

            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => hash('sha256', 'device-fingerprint'),
                'user_identifier' => null,
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Create a token with excessive nbf skew
            $privateKeyPath = config('jwt.private_key');
            $privateKeyContent = file_exists($privateKeyPath)
                ? File::get($privateKeyPath)
                : $privateKeyPath;

            $publicKeyPath = config('jwt.public_key_path');
            $publicKeyContent = File::get($publicKeyPath);

            $config = Configuration::forAsymmetricSigner(
                new Sha256(),
                InMemory::plainText($privateKeyContent),
                InMemory::plainText($publicKeyContent)
            );

            // Use a past timestamp for iat so nbf is not in the future relative to now
            // but nbf - iat is still > 300 seconds
            $iat = \DateTimeImmutable::createFromMutable(now()->subSeconds($nbfSkewSeconds + 60));
            $ttlSeconds = $ttlHours * 3600;
            $expiresAt = $iat->modify("+{$ttlSeconds} seconds");
            $nbf = $iat->modify("+{$nbfSkewSeconds} seconds");

            $jti = \Illuminate\Support\Str::uuid()->toString();

            $token = $config->builder()
                ->issuedBy(config('jwt.issuer'))
                ->permittedFor($product->slug)
                ->identifiedBy($jti)
                ->issuedAt($iat)
                ->canOnlyBeUsedAfter($nbf) // nbf - iat > 300 seconds
                ->expiresAt($expiresAt)
                ->relatedTo(hash('sha256', $license->key_hash))
                ->withClaim('device_fp_hash', $activation->device_fp_hash)
                ->withClaim('license_model', $license->license_model)
                ->withClaim('license_expiry', $license->expiry_date?->format('c') ?? null)
                ->getToken($config->signer(), $config->signingKey());

            $tokenString = $token->toString();

            // Save JTI to database (required for verification)
            \App\Models\OfflineTokenJti::create([
                'license_id' => $license->id,
                'jti'        => $jti,
                'expires_at' => now()->addSeconds($ttlSeconds),
                'is_revoked' => false,
            ]);

            // Verify that the token with excessive nbf skew is rejected
            $this->expectException(InvalidTokenException::class);
            $this->expectExceptionMessage('Token nbf claim too far in future');

            $this->tokenService->verify($tokenString, $product);
        });
    }

    /**
     * Property 11: Token with exp - iat > max TTL is rejected
     *
     * For any token where the TTL (exp - iat) exceeds the product's
     * maximum offline_token_ttl_hours, verification must fail with INVALID_TOKEN.
     *
     */
    public function property_token_with_excessive_ttl_is_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Random product TTL from 1 to 100 hours
            Generator\choose(1, 100),
            // Random excess hours from 1 to 68 (to ensure token TTL > product TTL)
            Generator\choose(1, 68)
        )->then(function (int $productTtlHours, int $excessHours) {
            // Create product with specific TTL
            $product = Product::create([
                'name'                    => 'Test Product',
                'slug'                    => 'test-product-' . uniqid(),
                'status'                  => 'active',
                'offline_token_ttl_hours' => $productTtlHours,
                'api_key'                 => 'test-api-key-' . uniqid(),
            ]);

            $license = License::create([
                'product_id'    => $product->id,
                'key_hash'      => hash('sha256', 'TEST-KEY-' . uniqid()),
                'key_last4'     => '1234',
                'license_model' => 'per-device',
                'status'        => 'active',
                'expiry_date'   => now()->addYear(),
            ]);

            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => hash('sha256', 'device-fingerprint'),
                'user_identifier' => null,
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Create a token with TTL exceeding product's max TTL
            $privateKeyPath = config('jwt.private_key');
            $privateKeyContent = file_exists($privateKeyPath)
                ? File::get($privateKeyPath)
                : $privateKeyPath;

            $publicKeyPath = config('jwt.public_key_path');
            $publicKeyContent = File::get($publicKeyPath);

            $config = Configuration::forAsymmetricSigner(
                new Sha256(),
                InMemory::plainText($privateKeyContent),
                InMemory::plainText($publicKeyContent)
            );

            $now = \DateTimeImmutable::createFromMutable(now());
            $excessiveTtlSeconds = ($productTtlHours + $excessHours) * 3600;
            $expiresAt = $now->modify("+{$excessiveTtlSeconds} seconds");

            $jti = \Illuminate\Support\Str::uuid()->toString();

            $token = $config->builder()
                ->issuedBy(config('jwt.issuer'))
                ->permittedFor($product->slug)
                ->identifiedBy($jti)
                ->issuedAt($now)
                ->canOnlyBeUsedAfter($now)
                ->expiresAt($expiresAt)
                ->relatedTo(hash('sha256', $license->key_hash))
                ->withClaim('device_fp_hash', $activation->device_fp_hash)
                ->withClaim('license_model', $license->license_model)
                ->withClaim('license_expiry', $license->expiry_date?->format('c') ?? null)
                ->getToken($config->signer(), $config->signingKey());

            $tokenString = $token->toString();

            // Save JTI to database (required for verification)
            \App\Models\OfflineTokenJti::create([
                'license_id' => $license->id,
                'jti'        => $jti,
                'expires_at' => now()->addSeconds($excessiveTtlSeconds),
                'is_revoked' => false,
            ]);

            // Verify that the token with excessive TTL is rejected
            $this->expectException(InvalidTokenException::class);
            $this->expectExceptionMessage('Token TTL exceeds product maximum');

            $this->tokenService->verify($tokenString, $product);
        });
    }

    /**
     * Property 11: Valid token with correct RS256 signature always passes verification
     *
     * For any valid token issued by the system with RS256 signature,
     * verification must succeed and return the correct claims.
     *
     */
    public function property_valid_token_with_correct_signature_always_passes(): void
    {
        $this->limitTo(100)->forAll(
            // Random TTL from 1 to 168 hours
            Generator\choose(1, 168),
            // Random device fingerprint
            Generator\suchThat(
                fn($s) => \strlen($s) >= 10 && \strlen($s) <= 255,
                Generator\string()
            )
        )->then(function (int $ttlHours, string $deviceFingerprint) {
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
                'license_model' => 'per-device',
                'status'        => 'active',
                'expiry_date'   => now()->addYear(),
            ]);

            $deviceFpHash = hash('sha256', $deviceFingerprint);
            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => $deviceFpHash,
                'user_identifier' => null,
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Issue a valid token
            $validTokenString = $this->tokenService->issue($activation, $product);

            // Verify the token - should not throw exception
            $claims = $this->tokenService->verify($validTokenString, $product);

            // Verify that all expected claims are present
            $this->assertArrayHasKey('jti', $claims);
            $this->assertArrayHasKey('iss', $claims);
            $this->assertArrayHasKey('aud', $claims);
            $this->assertArrayHasKey('sub', $claims);
            $this->assertArrayHasKey('iat', $claims);
            $this->assertArrayHasKey('nbf', $claims);
            $this->assertArrayHasKey('exp', $claims);
            $this->assertArrayHasKey('device_fp_hash', $claims);
            $this->assertArrayHasKey('license_model', $claims);
            $this->assertArrayHasKey('license_expiry', $claims);

            // Verify claim values
            $this->assertEquals('license-platform', $claims['iss']);
            $this->assertEquals($product->slug, $claims['aud']);
            $this->assertEquals($deviceFpHash, $claims['device_fp_hash']);
            $this->assertEquals('per-device', $claims['license_model']);

            // Verify TTL
            $actualTtl = $claims['exp'] - $claims['iat'];
            $expectedTtl = $ttlHours * 3600;
            $this->assertEquals($expectedTtl, $actualTtl);

            // Verify nbf - iat <= 300 seconds
            $nbfSkew = $claims['nbf'] - $claims['iat'];
            $this->assertLessThanOrEqual(300, $nbfSkew);
        });
    }

    /**
     * Property 11: Token with revoked JTI is rejected
     *
     * For any valid token whose JTI has been marked as revoked,
     * verification must fail with INVALID_TOKEN.
     *
     */
    public function property_token_with_revoked_jti_is_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Random TTL from 1 to 168 hours
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
                'license_model' => 'per-device',
                'status'        => 'active',
                'expiry_date'   => now()->addYear(),
            ]);

            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => hash('sha256', 'device-fingerprint'),
                'user_identifier' => null,
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Issue a valid token
            $validTokenString = $this->tokenService->issue($activation, $product);

            // Parse the token to get the JTI
            $parser = new Parser(new JoseEncoder());
            /** @var \Lcobucci\JWT\Token\Plain $token */
            $token = $parser->parse($validTokenString);
            $jti = $token->claims()->get('jti');

            // Revoke the JTI
            \App\Models\OfflineTokenJti::where('jti', $jti)->update(['is_revoked' => true]);

            // Verify that the token with revoked JTI is rejected
            $this->expectException(InvalidTokenException::class);
            $this->expectExceptionMessage('Token has been revoked');

            $this->tokenService->verify($validTokenString, $product);
        });
    }

    /**
     * Property 11: Token with expired JTI is rejected
     *
     * For any valid token whose JTI has expired (expires_at in the past),
     * verification must fail with INVALID_TOKEN.
     *
     */
    public function property_token_with_expired_jti_is_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Random TTL from 1 to 168 hours
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
                'license_model' => 'per-device',
                'status'        => 'active',
                'expiry_date'   => now()->addYear(),
            ]);

            $activation = Activation::create([
                'license_id'      => $license->id,
                'device_fp_hash'  => hash('sha256', 'device-fingerprint'),
                'user_identifier' => null,
                'type'            => 'per-device',
                'activated_at'    => now(),
                'is_active'       => true,
            ]);

            // Issue a valid token
            $validTokenString = $this->tokenService->issue($activation, $product);

            // Parse the token to get the JTI
            $parser = new Parser(new JoseEncoder());
            /** @var \Lcobucci\JWT\Token\Plain $token */
            $token = $parser->parse($validTokenString);
            $jti = $token->claims()->get('jti');

            // Set the JTI expires_at to the past
            \App\Models\OfflineTokenJti::where('jti', $jti)
                ->update(['expires_at' => now()->subHour()]);

            // Verify that the token with expired JTI is rejected
            $this->expectException(InvalidTokenException::class);
            $this->expectExceptionMessage('Token has expired');

            $this->tokenService->verify($validTokenString, $product);
        });
    }
}

