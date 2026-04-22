<?php

namespace Tests\Unit;

use App\Models\License;
use App\Models\Product;
use App\Services\LicenseKeyGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseKeyGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private LicenseKeyGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new LicenseKeyGenerator();
    }

    /**
     * Test that a single license key is generated in the correct format.
     */
    public function test_generate_single_key_format(): void
    {
        $key = $this->generator->generate();

        // Should match format XXXX-XXXX-XXXX-XXXX
        $this->assertMatchesRegularExpression(
            '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/',
            $key
        );
    }

    /**
     * Test that generated keys are unique.
     */
    public function test_generate_multiple_keys_are_unique(): void
    {
        $keys = [];
        for ($i = 0; $i < 10; $i++) {
            $keys[] = $this->generator->generate();
        }

        // All keys should be unique
        $this->assertCount(10, array_unique($keys));
    }

    /**
     * Test batch generation with valid count.
     */
    public function test_generate_batch_valid_count(): void
    {
        $keys = $this->generator->generateBatch(5);

        $this->assertCount(5, $keys);

        // All keys should match format
        foreach ($keys as $key) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/',
                $key
            );
        }

        // All keys should be unique
        $this->assertCount(5, array_unique($keys));
    }

    /**
     * Test batch generation with minimum size.
     */
    public function test_generate_batch_minimum_size(): void
    {
        $keys = $this->generator->generateBatch(1);
        $this->assertCount(1, $keys);
    }

    /**
     * Test batch generation with maximum size.
     */
    public function test_generate_batch_maximum_size(): void
    {
        $keys = $this->generator->generateBatch(100);
        $this->assertCount(100, $keys);
        $this->assertCount(100, array_unique($keys));
    }

    /**
     * Test batch generation with invalid count (too small).
     */
    public function test_generate_batch_invalid_count_too_small(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generateBatch(0);
    }

    /**
     * Test batch generation with invalid count (too large).
     */
    public function test_generate_batch_invalid_count_too_large(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generateBatch(101);
    }

    /**
     * Test that hash key returns correct SHA-256 hash.
     */
    public function test_hash_key_returns_sha256(): void
    {
        $plaintextKey = 'ABCD-EFGH-IJKL-MNOP';
        $hash = $this->generator->hashKey($plaintextKey);

        // SHA-256 hash should be 64 characters
        $this->assertStringMatchesFormat('%x', $hash);
        $this->assertSame(64, strlen($hash));

        // Hash should be deterministic
        $hash2 = $this->generator->hashKey($plaintextKey);
        $this->assertSame($hash, $hash2);
    }

    /**
     * Test that get_key_last4 returns last 4 characters.
     */
    public function test_get_key_last4_returns_last_four_chars(): void
    {
        $plaintextKey = 'ABCD-EFGH-IJKL-MNOP';
        $last4 = $this->generator->getKeyLast4($plaintextKey);

        $this->assertSame('MNOP', $last4);
    }

    /**
     * Test that generated key is unique in database.
     */
    public function test_generated_key_is_unique_in_database(): void
    {
        $product = Product::factory()->create();

        // Generate and save first key
        $key1 = $this->generator->generate();
        License::create([
            'product_id' => $product->id,
            'key_hash' => $this->generator->hashKey($key1),
            'key_last4' => $this->generator->getKeyLast4($key1),
            'license_model' => 'per-device',
            'status' => 'inactive',
        ]);

        // Generate second key - should be different
        $key2 = $this->generator->generate();
        $this->assertNotSame($key1, $key2);

        // Verify key2 is not in database yet
        $this->assertFalse(
            License::where('key_hash', $this->generator->hashKey($key2))->exists()
        );
    }

    /**
     * Test that batch generation respects database uniqueness.
     */
    public function test_batch_generation_respects_database_uniqueness(): void
    {
        $product = Product::factory()->create();

        // Generate and save first batch
        $batch1 = $this->generator->generateBatch(5);
        foreach ($batch1 as $key) {
            License::create([
                'product_id' => $product->id,
                'key_hash' => $this->generator->hashKey($key),
                'key_last4' => $this->generator->getKeyLast4($key),
                'license_model' => 'per-device',
                'status' => 'inactive',
            ]);
        }

        // Generate second batch - should not contain any keys from first batch
        $batch2 = $this->generator->generateBatch(5);

        // Merge both batches and verify all 10 keys are unique
        $merged = array_merge($batch1, $batch2);
        $this->assertCount(10, $merged);
        $this->assertCount(10, array_unique($merged));
    }

    /**
     * Test that soft-deleted licenses are considered when checking uniqueness.
     */
    public function test_soft_deleted_licenses_are_considered_for_uniqueness(): void
    {
        $product = Product::factory()->create();

        // Generate and save a key
        $key = $this->generator->generate();
        $license = License::create([
            'product_id' => $product->id,
            'key_hash' => $this->generator->hashKey($key),
            'key_last4' => $this->generator->getKeyLast4($key),
            'license_model' => 'per-device',
            'status' => 'inactive',
        ]);

        // Soft delete the license
        $license->delete();

        // Try to generate the same key - should fail because soft-deleted license is still considered
        $newKey = $this->generator->generate();
        $this->assertNotSame($key, $newKey);
    }
}
