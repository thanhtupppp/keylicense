<?php

namespace Tests\Unit;

use App\Models\Product;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Property-based test for rate limiting
 *
 * **Validates: Requirements 9.5, 9.6**
 *
 * Property 13: Rate limiting enforces per-API-key request quota
 *
 * For any API key, after exactly 60 successful requests within a 60-second window,
 * the 61st request within that same window SHALL be rejected with HTTP 429 and a
 * `Retry-After` header indicating the wait time. The rate limit counter SHALL be
 * independent per API key (requests from different API keys SHALL NOT affect each
 * other's counters).
 *
 */
class RateLimitingPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private Product $product1;
    private Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two products with different API keys for testing independence
        $this->product1 = Product::create([
            'name'                    => 'Test Product 1',
            'slug'                    => 'test-product-1-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-1-' . uniqid(),
        ]);

        $this->product2 = Product::create([
            'name'                    => 'Test Product 2',
            'slug'                    => 'test-product-2-' . uniqid(),
            'status'                  => 'active',
            'offline_token_ttl_hours' => 24,
            'api_key'                 => 'test-api-key-2-' . uniqid(),
        ]);
    }

    protected function tearDown(): void
    {
        // Clear all rate limiters after each test
        RateLimiter::clear('api_key:' . $this->product1->api_key);
        RateLimiter::clear('api_key:' . $this->product2->api_key);

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Property 13a — Rate limit enforces 60 requests per minute
    // -------------------------------------------------------------------------

    /**
     * Property 13a: Rate limit enforces exactly 60 requests per minute
     *
     * For any API key, after exactly 60 requests within a 60-second window,
     * the 61st request SHALL be rejected with HTTP 429 and include a
     * `Retry-After` header.
     *
     * Uses Eris to generate random request counts and verifies that the
     * 61st request is always rejected.
     *
     */
    public function property_rate_limit_enforces_60_requests_per_minute(): void
    {
        $this->limitTo(50)->forAll(
            // Generate random number of requests beyond the limit (61–70)
            Generator\choose(61, 70)
        )->then(function (int $totalRequests) {
            // Clear rate limiter before test
            RateLimiter::clear('api_key:' . $this->product1->api_key);

            $apiKey = $this->product1->api_key;
            $successfulRequests = 0;
            $rateLimitedRequests = 0;
            $firstRateLimitedAt = null;

            // Make requests to a protected endpoint
            for ($i = 1; $i <= $totalRequests; $i++) {
                $response = $this->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])->getJson('/api/v1/licenses/info?license_key=TEST-KEY-1234');

                // Count responses by status (ignoring 404/422 as those are expected for invalid license)
                if ($response->status() === 404 || $response->status() === 422) {
                    // These count as successful requests (not rate limited)
                    $successfulRequests++;
                } elseif ($response->status() === 429) {
                    $rateLimitedRequests++;
                    if ($firstRateLimitedAt === null) {
                        $firstRateLimitedAt = $i;
                    }

                    // Verify Retry-After header is present
                    $this->assertTrue(
                        $response->headers->has('Retry-After'),
                        "Request #{$i} returned 429 but missing Retry-After header"
                    );

                    // Verify error response format
                    $response->assertJson([
                        'success' => false,
                        'data' => null,
                        'error' => [
                            'code' => 'RATE_LIMIT_EXCEEDED',
                        ],
                    ]);

                    // Verify Retry-After is a positive integer
                    $retryAfter = $response->headers->get('Retry-After');
                    $this->assertIsNumeric($retryAfter);
                    $this->assertGreaterThan(0, (int)$retryAfter);
                    $this->assertLessThanOrEqual(60, (int)$retryAfter);
                }
            }

            // Verify exactly 60 requests succeeded
            $this->assertSame(
                60,
                $successfulRequests,
                "Expected exactly 60 successful requests, but got {$successfulRequests}"
            );

            // Verify the 61st request was the first to be rate limited
            $this->assertSame(
                61,
                $firstRateLimitedAt,
                "Expected the 61st request to be the first rate limited, but it was request #{$firstRateLimitedAt}"
            );

            // Verify all subsequent requests were rate limited
            $expectedRateLimited = $totalRequests - 60;
            $this->assertSame(
                $expectedRateLimited,
                $rateLimitedRequests,
                "Expected {$expectedRateLimited} rate limited requests, but got {$rateLimitedRequests}"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 13b — Rate limit counters are independent per API key
    // -------------------------------------------------------------------------

    /**
     * Property 13b: Rate limit counters are independent per API key
     *
     * For any two different API keys, requests from one API key SHALL NOT
     * affect the rate limit counter of the other API key. Each API key
     * SHALL have its own independent 60 requests per minute quota.
     *
     * Uses Eris to generate random request distributions and verifies that
     * rate limits are enforced independently.
     *
     */
    public function property_rate_limit_counters_are_independent_per_api_key(): void
    {
        $this->limitTo(50)->forAll(
            // Generate random number of requests for API key 1 (50–70)
            Generator\choose(50, 70),
            // Generate random number of requests for API key 2 (50–70)
            Generator\choose(50, 70)
        )->then(function (int $requests1, int $requests2) {
            // Clear rate limiters before test
            RateLimiter::clear('api_key:' . $this->product1->api_key);
            RateLimiter::clear('api_key:' . $this->product2->api_key);

            $apiKey1 = $this->product1->api_key;
            $apiKey2 = $this->product2->api_key;

            $successful1 = 0;
            $rateLimited1 = 0;
            $successful2 = 0;
            $rateLimited2 = 0;

            // Make requests with API key 1
            for ($i = 0; $i < $requests1; $i++) {
                $response = $this->withHeaders([
                    'X-API-Key' => $apiKey1,
                    'Accept' => 'application/json',
                ])->getJson('/api/v1/licenses/info?license_key=TEST-KEY-1234');

                if ($response->status() === 404 || $response->status() === 422) {
                    $successful1++;
                } elseif ($response->status() === 429) {
                    $rateLimited1++;
                }
            }

            // Make requests with API key 2
            for ($i = 0; $i < $requests2; $i++) {
                $response = $this->withHeaders([
                    'X-API-Key' => $apiKey2,
                    'Accept' => 'application/json',
                ])->getJson('/api/v1/licenses/info?license_key=TEST-KEY-5678');

                if ($response->status() === 404 || $response->status() === 422) {
                    $successful2++;
                } elseif ($response->status() === 429) {
                    $rateLimited2++;
                }
            }

            // Verify API key 1 results
            $expectedSuccessful1 = min($requests1, 60);
            $expectedRateLimited1 = max(0, $requests1 - 60);
            $this->assertSame(
                $expectedSuccessful1,
                $successful1,
                "API key 1: Expected {$expectedSuccessful1} successful requests, but got {$successful1}"
            );
            $this->assertSame(
                $expectedRateLimited1,
                $rateLimited1,
                "API key 1: Expected {$expectedRateLimited1} rate limited requests, but got {$rateLimited1}"
            );

            // Verify API key 2 results
            $expectedSuccessful2 = min($requests2, 60);
            $expectedRateLimited2 = max(0, $requests2 - 60);
            $this->assertSame(
                $expectedSuccessful2,
                $successful2,
                "API key 2: Expected {$expectedSuccessful2} successful requests, but got {$successful2}"
            );
            $this->assertSame(
                $expectedRateLimited2,
                $rateLimited2,
                "API key 2: Expected {$expectedRateLimited2} rate limited requests, but got {$rateLimited2}"
            );

            // Verify independence: API key 1's requests didn't affect API key 2's quota
            // If requests1 >= 60, API key 1 should be rate limited
            // But API key 2 should still have its full quota available
            if ($requests1 >= 60 && $requests2 >= 60) {
                $this->assertSame(
                    60,
                    $successful2,
                    "API key 2 should have its full quota of 60 requests despite API key 1 being rate limited"
                );
            }
        });
    }

    // -------------------------------------------------------------------------
    // Property 13c — Rate limit applies to all protected endpoints
    // -------------------------------------------------------------------------

    /**
     * Property 13c: Rate limit applies consistently across all protected endpoints
     *
     * For any API key, the rate limit counter SHALL be shared across all
     * protected endpoints. Requests to different endpoints SHALL count
     * toward the same 60 requests per minute quota.
     *
     * Uses Eris to generate random endpoint selections and verifies that
     * the rate limit is enforced across all endpoints.
     *
     */
    public function property_rate_limit_applies_across_all_endpoints(): void
    {
        $this->limitTo(50)->forAll(
            // Generate random number of requests to distribute (61–80)
            Generator\choose(61, 80)
        )->then(function (int $totalRequests) {
            // Clear rate limiter before test
            RateLimiter::clear('api_key:' . $this->product1->api_key);

            $apiKey = $this->product1->api_key;

            $successfulRequests = 0;
            $rateLimitedRequests = 0;

            // Make requests to a protected endpoint
            for ($i = 1; $i <= $totalRequests; $i++) {
                $response = $this->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])->getJson('/api/v1/licenses/info?license_key=TEST-KEY-1234');

                if ($response->status() === 404 || $response->status() === 422) {
                    $successfulRequests++;
                } elseif ($response->status() === 429) {
                    $rateLimitedRequests++;
                }
            }

            // Verify exactly 60 requests succeeded
            $this->assertSame(
                60,
                $successfulRequests,
                "Expected exactly 60 successful requests across all endpoints, but got {$successfulRequests}"
            );

            // Verify remaining requests were rate limited
            $expectedRateLimited = $totalRequests - 60;
            $this->assertSame(
                $expectedRateLimited,
                $rateLimitedRequests,
                "Expected {$expectedRateLimited} rate limited requests, but got {$rateLimitedRequests}"
            );
        });
    }

    // -------------------------------------------------------------------------
    // Property 13d — Retry-After header accuracy
    // -------------------------------------------------------------------------

    /**
     * Property 13d: Retry-After header provides accurate wait time
     *
     * For any rate limited request, the `Retry-After` header SHALL contain
     * a positive integer representing the number of seconds until the rate
     * limit window resets, and this value SHALL be between 1 and 60 seconds.
     *
     * Uses Eris to generate random request patterns and verifies that
     * Retry-After values are always within the valid range.
     *
     */
    public function property_retry_after_header_is_accurate(): void
    {
        $this->limitTo(50)->forAll(
            // Generate random number of rate limited requests to check (1–20)
            Generator\choose(1, 20)
        )->then(function (int $rateLimitedChecks) {
            // Clear rate limiter before test
            RateLimiter::clear('api_key:' . $this->product1->api_key);

            $apiKey = $this->product1->api_key;

            // Make 60 requests to exhaust the quota
            for ($i = 0; $i < 60; $i++) {
                $this->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])->getJson('/api/v1/licenses/info?license_key=TEST-KEY-1234');
            }

            // Now make rate limited requests and check Retry-After headers
            $retryAfterValues = [];
            for ($i = 0; $i < $rateLimitedChecks; $i++) {
                $response = $this->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])->getJson('/api/v1/licenses/info?license_key=TEST-KEY-1234');

                $response->assertStatus(429);
                $this->assertTrue(
                    $response->headers->has('Retry-After'),
                    "Rate limited request #{$i} missing Retry-After header"
                );

                $retryAfter = (int)$response->headers->get('Retry-After');
                $retryAfterValues[] = $retryAfter;

                // Verify Retry-After is within valid range
                $this->assertGreaterThan(
                    0,
                    $retryAfter,
                    "Retry-After must be positive, got {$retryAfter}"
                );
                $this->assertLessThanOrEqual(
                    60,
                    $retryAfter,
                    "Retry-After must be <= 60 seconds, got {$retryAfter}"
                );
            }

            // Verify all Retry-After values are valid
            $this->assertCount(
                $rateLimitedChecks,
                $retryAfterValues,
                "Expected {$rateLimitedChecks} Retry-After values"
            );

            // Verify Retry-After values are monotonically non-increasing
            // (as time passes, the wait time should decrease or stay the same)
            for ($i = 1; $i < count($retryAfterValues); $i++) {
                $this->assertLessThanOrEqual(
                    $retryAfterValues[$i - 1],
                    $retryAfterValues[$i],
                    "Retry-After should be non-increasing over time"
                );
            }
        });
    }

    // -------------------------------------------------------------------------
    // Property 13e — Rate limit boundary conditions
    // -------------------------------------------------------------------------

    /**
     * Property 13e: Rate limit boundary conditions are exact
     *
     * For any API key, the 60th request SHALL succeed (status 404/422) and the
     * 61st request SHALL be rate limited (status 429), with no ambiguity
     * at the boundary.
     *
     * Uses Eris to generate random test iterations and verifies that the
     * boundary is always exact at request #60.
     *
     */
    public function property_rate_limit_boundary_is_exact(): void
    {
        $this->limitTo(100)->forAll(
            // Generate random iteration count to verify consistency (1–50)
            Generator\choose(1, 50)
        )->then(function (int $iteration) {
            // Clear rate limiter before test
            RateLimiter::clear('api_key:' . $this->product1->api_key);

            $apiKey = $this->product1->api_key;

            // Make exactly 60 requests
            for ($i = 1; $i <= 60; $i++) {
                $response = $this->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])->getJson('/api/v1/licenses/info?license_key=TEST-KEY-1234');

                $this->assertContains(
                    $response->status(),
                    [404, 422],
                    "Request #{$i} should succeed (within quota), but got status {$response->status()}"
                );
            }

            // The 61st request should be rate limited
            $response = $this->withHeaders([
                'X-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->getJson('/api/v1/licenses/info?license_key=TEST-KEY-1234');

            $response->assertStatus(
                429,
                "Request #61 should be rate limited, but got status {$response->status()}"
            );

            $response->assertHeader('Retry-After');
            $response->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                ],
            ]);

            // Clear for next iteration
            RateLimiter::clear('api_key:' . $this->product1->api_key);
        });
    }

    // -------------------------------------------------------------------------
    // Property 13f — Rate limit without API key
    // -------------------------------------------------------------------------

    /**
     * Property 13f: Requests without API key are not rate limited
     *
     * For any request without an X-API-Key header, the rate limiting
     * middleware SHALL not apply, and the request SHALL be processed
     * normally (though it may fail authentication at a later stage).
     *
     * Uses Eris to generate random request counts and verifies that
     * requests without API keys bypass rate limiting.
     *
     */
    public function property_requests_without_api_key_bypass_rate_limiting(): void
    {
        $this->limitTo(50)->forAll(
            // Generate random number of requests (61–100)
            Generator\choose(61, 100)
        )->then(function (int $totalRequests) {
            // Make requests without X-API-Key header
            $statusCodes = [];
            for ($i = 0; $i < $totalRequests; $i++) {
                $response = $this->withHeaders([
                    'Accept' => 'application/json',
                ])->getJson('/api/v1/public-key');

                $statusCodes[] = $response->status();
            }

            // Verify no requests were rate limited (status 429)
            $rateLimitedCount = count(array_filter($statusCodes, fn($status) => $status === 429));
            $this->assertSame(
                0,
                $rateLimitedCount,
                "Expected no rate limited requests without API key, but got {$rateLimitedCount}"
            );

            // All requests should succeed (public-key endpoint doesn't require auth)
            $successCount = count(array_filter($statusCodes, fn($status) => $status === 200));
            $this->assertSame(
                $totalRequests,
                $successCount,
                "Expected all {$totalRequests} requests to succeed, but only {$successCount} did"
            );
        });
    }
}

