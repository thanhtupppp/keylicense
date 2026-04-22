<?php

namespace Tests\Integration;

use App\Models\License;
use App\Models\Product;
use App\States\License\ActiveState;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;

/**
 * Integration test for rate limiting with real Redis.
 * Tests rate limiting enforcement with actual Redis instance.
 * 
 * Requirements: T7, 9.5, 9.6
 */
class RateLimitingTest extends IntegrationTestCase
{
    /**
     * Test rate limiting enforces 60 requests per minute per API key.
     * 
     */
    public function test_rate_limiting_enforces_60_requests_per_minute(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'RATE-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TKEY',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        // Clear any existing rate limit data
        RateLimiter::clear('api_key:' . $product->api_key);

        // Create an activation so validate requests return 200 instead of 422
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ])->assertStatus(200);

        // Act: Send exactly 59 validation requests after the activation call above.
        // The activation request also consumes one request in the shared API-key bucket.
        $successCount = 0;
        for ($i = 1; $i <= 59; $i++) {
            $response = $this->postJson('/api/v1/licenses/validate', [
                'license_key' => $licenseKey,
                'device_fingerprint' => 'test-device',
            ], [
                'X-API-Key' => $product->api_key,
            ]);

            if ($response->status() === 200) {
                $successCount++;
            }
        }

        // Assert: All validation requests succeeded
        $this->assertEquals(59, $successCount, 'Expected all 59 validation requests to succeed');

        // Act: Send 61st request
        $response61 = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert: 61st request is rate limited
        $response61->assertStatus(429);
        $response61->assertJson([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
            ],
        ]);

        // Assert: Retry-After header is present
        $this->assertTrue(
            $response61->headers->has('Retry-After'),
            'Expected Retry-After header to be present'
        );

        $retryAfter = (int) $response61->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter, 'Retry-After should be greater than 0');
        $this->assertLessThanOrEqual(60, $retryAfter, 'Retry-After should be at most 60 seconds');
    }

    /**
     * Test rate limiting is independent per API key.
     * 
     */
    public function test_rate_limiting_is_independent_per_api_key(): void
    {
        // Arrange: Create two products with different API keys
        $product1 = Product::factory()->create([
            'status' => 'active',
            'slug' => 'product-1',
        ]);

        $product2 = Product::factory()->create([
            'status' => 'active',
            'slug' => 'product-2',
        ]);

        $license1Key = 'PRO1-1234-5678-9012';
        $license1Hash = hash('sha256', $license1Key);

        $license2Key = 'PRO2-1234-5678-9012';
        $license2Hash = hash('sha256', $license2Key);

        $license1 = License::factory()->create([
            'product_id' => $product1->id,
            'key_hash' => $license1Hash,
            'key_last4' => 'KEY1',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        $license2 = License::factory()->create([
            'product_id' => $product2->id,
            'key_hash' => $license2Hash,
            'key_last4' => 'KEY2',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        // Clear any existing rate limit data
        RateLimiter::clear('api_key:' . $product1->api_key);
        RateLimiter::clear('api_key:' . $product2->api_key);

        // Create activations so validate requests return 200 instead of 422
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $license1Key,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product1->api_key,
        ])->assertStatus(200);

        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $license2Key,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product2->api_key,
        ])->assertStatus(200);

        // Act: Send 60 requests with product1 API key
        for ($i = 1; $i <= 60; $i++) {
            $this->postJson('/api/v1/licenses/validate', [
                'license_key' => $license1Key,
                'device_fingerprint' => 'test-device',
            ], [
                'X-API-Key' => $product1->api_key,
            ]);
        }

        // Act: 61st request with product1 API key should be rate limited
        $response1 = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $license1Key,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product1->api_key,
        ]);

        // Act: Request with product2 API key should succeed
        $response2 = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $license2Key,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product2->api_key,
        ]);

        // Assert: product1 is rate limited
        $response1->assertStatus(429);

        // Assert: product2 is NOT rate limited (independent counter)
        $response2->assertStatus(200);
    }

    /**
     * Test rate limit resets after time window expires.
     * 
     */
    public function test_rate_limit_resets_after_time_window(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'REST-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TKEY',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        // Clear any existing rate limit data
        RateLimiter::clear('api_key:' . $product->api_key);

        // Create activation so validate requests return 200 instead of 422
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ])->assertStatus(200);

        // Act: Send 60 requests to hit the limit
        for ($i = 1; $i <= 60; $i++) {
            $this->postJson('/api/v1/licenses/validate', [
                'license_key' => $licenseKey,
                'device_fingerprint' => 'test-device',
            ], [
                'X-API-Key' => $product->api_key,
            ]);
        }

        // Verify rate limit is active
        $response = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(429);

        // Act: Manually clear rate limit (simulating time window expiry)
        RateLimiter::clear('api_key:' . $product->api_key);

        // Act: Try request again
        $responseAfterReset = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert: Request succeeds after reset
        $responseAfterReset->assertStatus(200);
    }

    /**
     * Test rate limiting applies to all API endpoints except public key.
     * 
     */
    public function test_rate_limiting_applies_to_all_endpoints_except_public_key(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'ALL1-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TKEY',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        // Clear any existing rate limit data
        RateLimiter::clear('api_key:' . $product->api_key);

        // Create activation so validate requests return 200 instead of 422
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ])->assertStatus(200);

        // Act: Send 60 requests to validate endpoint
        for ($i = 1; $i <= 60; $i++) {
            $this->postJson('/api/v1/licenses/validate', [
                'license_key' => $licenseKey,
                'device_fingerprint' => 'test-device',
            ], [
                'X-API-Key' => $product->api_key,
            ]);
        }

        // Act: Try info endpoint (should be rate limited)
        $infoResponse = $this->getJson('/api/v1/licenses/info?license_key=' . $licenseKey, [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert: info endpoint is rate limited
        $infoResponse->assertStatus(429);

        // Act: Try public key endpoint (should NOT be rate limited)
        $publicKeyResponse = $this->getJson('/api/v1/public-key');

        // Assert: public key endpoint is NOT rate limited
        $publicKeyResponse->assertStatus(200);
    }

    /**
     * Test rate limiter counter is tracked correctly for API key bucket.
     * 
     */
    public function test_rate_limiting_stores_correct_counter_in_redis(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'CNT1-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TKEY',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        $rateLimitKey = 'api_key:' . $product->api_key;

        // Clear any existing rate limit data
        RateLimiter::clear($rateLimitKey);

        // Create activation so validate requests return 200 instead of 422
        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ])->assertStatus(200);

        // Act: Send 10 requests
        for ($i = 1; $i <= 10; $i++) {
            $this->postJson('/api/v1/licenses/validate', [
                'license_key' => $licenseKey,
                'device_fingerprint' => 'test-device',
            ], [
                'X-API-Key' => $product->api_key,
            ])->assertStatus(200);
        }

        // Assert: remaining should be 49 (60 max - 1 activation - 10 validates)
        $this->assertSame(49, RateLimiter::remaining($rateLimitKey, 60));

        // Act: consume remaining 49 slots
        for ($i = 1; $i <= 49; $i++) {
            $this->postJson('/api/v1/licenses/validate', [
                'license_key' => $licenseKey,
                'device_fingerprint' => 'test-device',
            ], [
                'X-API-Key' => $product->api_key,
            ])->assertStatus(200);
        }

        // Act: next request should be blocked
        $response = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert: Rate limit is enforced
        $response->assertStatus(429);
    }

    /**
     * Test rate limiting with different endpoints share same counter.
     * 
     */
    public function test_rate_limiting_shared_across_different_endpoints(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $licenseKey = 'SHR1-1234-5678-9012';
        $keyHash = hash('sha256', $licenseKey);

        $license = License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'TKEY',
            'license_model' => 'per-device',
            'status' => new ActiveState(new License()),
        ]);

        // Clear any existing rate limit data
        RateLimiter::clear('api_key:' . $product->api_key);

        // Act: Send 30 requests to validate endpoint
        for ($i = 1; $i <= 30; $i++) {
            $this->postJson('/api/v1/licenses/validate', [
                'license_key' => $licenseKey,
                'device_fingerprint' => 'test-device',
            ], [
                'X-API-Key' => $product->api_key,
            ]);
        }

        // Act: Send 30 requests to info endpoint
        for ($i = 1; $i <= 30; $i++) {
            $this->getJson('/api/v1/licenses/info?license_key=' . $licenseKey, [
                'X-API-Key' => $product->api_key,
            ]);
        }

        // Act: 61st request (to any endpoint) should be rate limited
        $response = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        // Assert: Rate limit is enforced (30 + 30 = 60, so 61st is blocked)
        $response->assertStatus(429);
    }
}

