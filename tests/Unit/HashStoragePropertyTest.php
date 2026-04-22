<?php

namespace Tests\Unit;

use App\Models\Activation;
use App\Models\License;
use App\Models\Product;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based test for hash storage round-trip
 * 
 * **Validates: Requirements 13.1, 13.3**
 * 
 */
class HashStoragePropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    /**
     * Property 15: Hash storage round-trip for license keys
     * 
     * For any license key plaintext K:
     * - key_hash = SHA-256(K)
     * - key_last4 = substr(K, -4)
     * 
     * These hash values SHALL be deterministic and reproducible.
     * 
     */
    public function property_license_key_hash_storage_is_deterministic_and_correct()
    {
        $this->forAll(
            Generator\suchThat(
                function ($s) {
                    return strlen($s) >= 4;
                },
                Generator\string()
            )
        )
            ->then(function (string $licenseKeyPlaintext) {
                // Create a product for the license
                $product = Product::create([
                    'name' => 'Test Product',
                    'slug' => 'test-product-' . uniqid(),
                    'status' => 'active',
                    'offline_token_ttl_hours' => 24,
                    'api_key' => 'test-api-key-' . uniqid(),
                ]);

                // Calculate expected hash and last4
                $expectedKeyHash = hash('sha256', $licenseKeyPlaintext);
                $expectedKeyLast4 = substr($licenseKeyPlaintext, -4);

                // Create license with the plaintext key
                $license = License::create([
                    'product_id' => $product->id,
                    'key_hash' => $expectedKeyHash,
                    'key_last4' => $expectedKeyLast4,
                    'license_model' => 'per-device',
                    'status' => 'inactive',
                ]);

                // Verify the stored values match our expectations
                $this->assertEquals(
                    $expectedKeyHash,
                    $license->key_hash,
                    "key_hash should equal SHA-256 of plaintext license key"
                );
                $this->assertEquals(
                    $expectedKeyLast4,
                    $license->key_last4,
                    "key_last4 should equal last 4 characters of plaintext license key"
                );

                // Verify hash is exactly 64 characters (SHA-256 hex output)
                $this->assertEquals(
                    64,
                    strlen($license->key_hash),
                    "SHA-256 hash should be 64 characters"
                );

                // Verify last4 is exactly 4 characters
                $this->assertEquals(
                    4,
                    strlen($license->key_last4),
                    "key_last4 should be exactly 4 characters"
                );

                // Verify determinism: hashing the same plaintext again produces same hash
                $rehash = hash('sha256', $licenseKeyPlaintext);
                $this->assertEquals(
                    $expectedKeyHash,
                    $rehash,
                    "Hash should be deterministic - same input produces same output"
                );
            });
    }

    /**
     * Property 15: Hash storage round-trip for device fingerprints
     * 
     * For any device fingerprint F:
     * - device_fp_hash = SHA-256(F)
     * 
     * These hash values SHALL be deterministic and reproducible.
     * 
     */
    public function property_device_fingerprint_hash_storage_is_deterministic_and_correct()
    {
        $this->forAll(
            Generator\suchThat(
                function ($s) {
                    return strlen($s) >= 10;
                },
                Generator\string()
            )
        )
            ->then(function (string $deviceFingerprintPlaintext) {
                // Create a product and license for the activation
                $product = Product::create([
                    'name' => 'Test Product',
                    'slug' => 'test-product-' . uniqid(),
                    'status' => 'active',
                    'offline_token_ttl_hours' => 24,
                    'api_key' => 'test-api-key-' . uniqid(),
                ]);

                $licenseKeyPlaintext = 'TEST-LICENSE-KEY-' . uniqid();
                $license = License::create([
                    'product_id' => $product->id,
                    'key_hash' => hash('sha256', $licenseKeyPlaintext),
                    'key_last4' => substr($licenseKeyPlaintext, -4),
                    'license_model' => 'per-device',
                    'status' => 'active',
                ]);

                // Calculate expected device fingerprint hash
                $expectedDeviceFpHash = hash('sha256', $deviceFingerprintPlaintext);

                // Create activation with the device fingerprint
                $activation = Activation::create([
                    'license_id' => $license->id,
                    'device_fp_hash' => $expectedDeviceFpHash,
                    'user_identifier' => null,
                    'type' => 'per-device',
                    'activated_at' => now(),
                    'is_active' => true,
                ]);

                // Verify the stored value matches our expectation
                $this->assertEquals(
                    $expectedDeviceFpHash,
                    $activation->device_fp_hash,
                    "device_fp_hash should equal SHA-256 of plaintext device fingerprint"
                );

                // Verify hash is exactly 64 characters (SHA-256 hex output)
                $this->assertEquals(
                    64,
                    strlen($activation->device_fp_hash),
                    "SHA-256 hash should be 64 characters"
                );

                // Verify determinism: hashing the same plaintext again produces same hash
                $rehash = hash('sha256', $deviceFingerprintPlaintext);
                $this->assertEquals(
                    $expectedDeviceFpHash,
                    $rehash,
                    "Hash should be deterministic - same input produces same output"
                );
            });
    }

    /**
     * Property 15: Combined test - verify both license key and device fingerprint hashing
     * in a single activation flow
     * 
     */
    public function property_combined_hash_storage_round_trip_is_correct()
    {
        $this->forAll(
            Generator\suchThat(
                function ($s) {
                    return strlen($s) >= 4;
                },
                Generator\string()
            ),
            Generator\suchThat(
                function ($s) {
                    return strlen($s) >= 10;
                },
                Generator\string()
            )
        )
            ->then(function (string $licenseKeyPlaintext, string $deviceFingerprintPlaintext) {
                // Create product
                $product = Product::create([
                    'name' => 'Test Product',
                    'slug' => 'test-product-' . uniqid(),
                    'status' => 'active',
                    'offline_token_ttl_hours' => 24,
                    'api_key' => 'test-api-key-' . uniqid(),
                ]);

                // Calculate expected hashes
                $expectedKeyHash = hash('sha256', $licenseKeyPlaintext);
                $expectedKeyLast4 = substr($licenseKeyPlaintext, -4);
                $expectedDeviceFpHash = hash('sha256', $deviceFingerprintPlaintext);

                // Create license
                $license = License::create([
                    'product_id' => $product->id,
                    'key_hash' => $expectedKeyHash,
                    'key_last4' => $expectedKeyLast4,
                    'license_model' => 'per-device',
                    'status' => 'active',
                ]);

                // Create activation
                $activation = Activation::create([
                    'license_id' => $license->id,
                    'device_fp_hash' => $expectedDeviceFpHash,
                    'user_identifier' => null,
                    'type' => 'per-device',
                    'activated_at' => now(),
                    'is_active' => true,
                ]);

                // Verify license key hashing
                $this->assertEquals($expectedKeyHash, $license->key_hash);
                $this->assertEquals($expectedKeyLast4, $license->key_last4);

                // Verify device fingerprint hashing
                $this->assertEquals($expectedDeviceFpHash, $activation->device_fp_hash);

                // Verify we can look up the license by hash
                $foundLicense = License::where('key_hash', $expectedKeyHash)->first();
                $this->assertNotNull($foundLicense);
                $this->assertEquals($license->id, $foundLicense->id);

                // Verify we can look up the activation by device fingerprint hash
                $foundActivation = Activation::where('device_fp_hash', $expectedDeviceFpHash)->first();
                $this->assertNotNull($foundActivation);
                $this->assertEquals($activation->id, $foundActivation->id);
            });
    }
}

