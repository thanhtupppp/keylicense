<?php

namespace Tests\Unit;

use App\Models\License;
use App\Models\Product;
use App\Services\LicenseKeyGenerator;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based test for license key format and uniqueness
 *
 * **Validates: Requirements 2.4, 2.5**
 *
 */
class LicenseKeyGeneratorPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private LicenseKeyGenerator $generator;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new LicenseKeyGenerator();

        $this->product = Product::create([
            'name'                    => 'Test Product',
            'slug'                    => 'test-product-keygen-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-keygen-' . uniqid(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Property 4a — License key format validation
    // -------------------------------------------------------------------------

    /**
     * Property 4: License key format and uniqueness
     *
     * For any batch size from 1–100: every key must match regex
     * `^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$`
     *
     * Uses Eris to generate random batch sizes within the valid range (1–100)
     * and verifies that all generated keys match the required format exactly.
     *
     */
    public function property_all_generated_keys_match_format(): void
    {
        $this->limitTo(100)->forAll(
            // Random batch size from 1 to 100
            Generator\choose(1, 100)
        )->then(function (int $batchSize) {
            $keys = $this->generator->generateBatch($batchSize);

            // Verify we got the requested number of keys
            $this->assertCount($batchSize, $keys);

            // Verify every key matches the required format
            $pattern = '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/';
            foreach ($keys as $key) {
                $this->assertMatchesRegularExpression(
                    $pattern,
                    $key,
                    "Generated key '{$key}' does not match required format"
                );
            }
        });
    }

    // -------------------------------------------------------------------------
    // Property 4b — License key uniqueness within batch
    // -------------------------------------------------------------------------

    /**
     * Property 4: No two keys in the same batch can be identical
     *
     * For any batch size from 1–100: all keys in the batch must be unique
     * (no duplicates within the same batch).
     *
     * Uses Eris to generate random batch sizes and verifies that the number
     * of unique keys equals the batch size.
     *
     */
    public function property_no_duplicate_keys_within_batch(): void
    {
        $this->limitTo(100)->forAll(
            // Random batch size from 1 to 100
            Generator\choose(1, 100)
        )->then(function (int $batchSize) {
            $keys = $this->generator->generateBatch($batchSize);

            // Count unique keys
            $uniqueKeys = array_unique($keys);

            // All keys should be unique
            $this->assertCount(
                $batchSize,
                $uniqueKeys,
                "Batch of {$batchSize} keys contains duplicates"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 4c — License key uniqueness across multiple batches
    // -------------------------------------------------------------------------

    /**
     * Property 4: No two keys in the entire system can be identical
     *
     * For any sequence of batch generations: all keys across all batches
     * must be unique (no key is ever generated twice, even across different
     * batch calls).
     *
     * Uses Eris to generate random batch sizes and verifies that keys from
     * multiple batches never collide.
     *
     */
    public function property_no_duplicate_keys_across_batches(): void
    {
        $this->limitTo(100)->forAll(
            // First batch size
            Generator\choose(1, 50),
            // Second batch size
            Generator\choose(1, 50)
        )->then(function (int $batchSize1, int $batchSize2) {
            // Generate first batch
            $batch1 = $this->generator->generateBatch($batchSize1);

            // Save first batch to database to ensure second batch respects uniqueness
            foreach ($batch1 as $key) {
                License::create([
                    'product_id'    => $this->product->id,
                    'key_hash'      => $this->generator->hashKey($key),
                    'key_last4'     => $this->generator->getKeyLast4($key),
                    'license_model' => 'per-device',
                    'status'        => 'inactive',
                ]);
            }

            // Generate second batch
            $batch2 = $this->generator->generateBatch($batchSize2);

            // Merge both batches
            $allKeys = array_merge($batch1, $batch2);

            // All keys across both batches should be unique
            $uniqueKeys = array_unique($allKeys);
            $this->assertCount(
                $batchSize1 + $batchSize2,
                $uniqueKeys,
                "Keys from batch 1 ({$batchSize1}) and batch 2 ({$batchSize2}) contain duplicates"
            );

            // Verify no key from batch2 is in batch1
            $batch1Set = array_flip($batch1);
            foreach ($batch2 as $key) {
                $this->assertArrayNotHasKey(
                    $key,
                    $batch1Set,
                    "Key '{$key}' from batch 2 was already in batch 1"
                );
            }
        });
    }

    // -------------------------------------------------------------------------
    // Property 4d — Hash storage correctness
    // -------------------------------------------------------------------------

    /**
     * Property 4: Hash storage round-trip correctness
     *
     * For any generated key: the stored key_hash must equal SHA-256(key)
     * and key_last4 must equal the last 4 characters of the key.
     *
     * Uses Eris to generate random batch sizes and verifies that the hash
     * and last4 extraction are correct for all generated keys.
     *
     */
    public function property_hash_storage_is_correct(): void
    {
        $this->limitTo(100)->forAll(
            // Random batch size from 1 to 100
            Generator\choose(1, 100)
        )->then(function (int $batchSize) {
            $keys = $this->generator->generateBatch($batchSize);

            foreach ($keys as $key) {
                // Verify key_hash is correct SHA-256
                $expectedHash = hash('sha256', $key);
                $actualHash = $this->generator->hashKey($key);
                $this->assertSame(
                    $expectedHash,
                    $actualHash,
                    "Hash for key '{$key}' is incorrect"
                );

                // Verify key_last4 is correct (last 4 characters)
                $expectedLast4 = substr($key, -4);
                $actualLast4 = $this->generator->getKeyLast4($key);
                $this->assertSame(
                    $expectedLast4,
                    $actualLast4,
                    "Last 4 characters for key '{$key}' is incorrect"
                );

                // Verify hash is 64 characters (SHA-256 hex output)
                $this->assertSame(64, strlen($actualHash));

                // Verify last4 is exactly 4 characters
                $this->assertSame(4, strlen($actualLast4));
            }
        });
    }
}

