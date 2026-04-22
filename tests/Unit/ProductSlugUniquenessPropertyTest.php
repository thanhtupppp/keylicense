<?php

namespace Tests\Unit;

use App\Models\Product;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-based test for product slug uniqueness
 *
 * **Property 2: Product slug uniqueness**
 * 
 * With two product creation requests using the same slug: the second request should always be 
 * rejected with validation error specifying that the slug already exists
 *
 * **Validates: Requirements 1.3**
 *
 */
class ProductSlugUniquenessPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    // -------------------------------------------------------------------------
    // Property 2 — Product slug uniqueness
    // -------------------------------------------------------------------------

    /**
     * Property 2: Product slug uniqueness
     *
     * For any two product creation requests using the same slug value, the second request
     * SHALL always be rejected with a validation error indicating the slug is already taken,
     * regardless of other field values.
     *
     * This test verifies that:
     * 1. The first product creation with a valid slug succeeds
     * 2. The second product creation with the same slug fails with uniqueness validation error
     * 3. The validation error specifically mentions that the slug already exists
     * 4. This behavior is consistent regardless of other field values
     *
     */
    public function property_duplicate_slug_always_rejected(): void
    {
        $this->limitTo(100)->forAll(
            // Generate valid slugs that match the required pattern
            Generator\elements([
                'test-app',
                'my-product',
                'web-service',
                'api-v1',
                'mobile-app',
                'desktop-client',
                'service-worker',
                'data-processor',
                'file-manager',
                'user-portal',
                'admin-panel',
                'payment-gateway',
                'notification-service',
                'auth-service',
                'content-manager',
                'media-player',
                'chat-app',
                'video-editor',
                'photo-gallery',
                'task-manager',
                'calendar-app',
                'weather-app',
                'news-reader',
                'music-player',
                'game-engine',
                'code-editor',
                'database-tool',
                'monitoring-tool',
                'backup-service',
                'sync-client',
                'crypto-wallet',
                'social-network',
                'e-commerce',
                'blog-platform',
                'forum-software',
                'wiki-engine',
                'cms-platform',
                'crm-system',
                'erp-solution',
                'hr-system',
                'inventory-manager',
                'pos-system',
                'booking-system',
                'survey-tool',
                'analytics-platform',
                'reporting-tool',
                'dashboard-app',
                'monitoring-dashboard',
                'log-analyzer',
                'performance-monitor',
                'security-scanner'
            ])
        )->then(function (string $slug) {
            // Generate a unique slug for this test iteration to avoid conflicts
            $uniqueSlug = $slug . '-' . uniqid();

            // First, create a product with this slug
            $firstProduct = Product::factory()->create([
                'slug' => $uniqueSlug,
                'name' => 'First Product',
                'api_key' => Str::random(32)
            ]);

            $this->assertDatabaseHas('products', [
                'slug' => $uniqueSlug,
                'name' => 'First Product'
            ]);

            // Now try to create a second product with the same slug but different other fields
            $secondProductData = [
                'name' => 'Second Product (Different Name)',
                'slug' => $uniqueSlug, // Same slug as first product
                'description' => 'This is a different description',
                'logo_url' => 'https://example.com/different-logo.png',
                'platforms' => ['Android', 'iOS'], // Different platforms
                'offline_token_ttl_hours' => 48, // Different TTL
            ];

            // Validate using Laravel's validation rules
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'slug' => [
                    'required',
                    'string',
                    'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
                    'unique:products,slug'
                ],
                'description' => ['nullable', 'string', 'max:1000'],
                'logo_url' => ['nullable', 'url'],
                'platforms' => ['nullable', 'array'],
                'platforms.*' => ['string', 'in:Windows,macOS,Linux,Android,iOS,Web'],
                'offline_token_ttl_hours' => ['required', 'integer', 'min:1', 'max:168'],
            ];

            $validator = Validator::make($secondProductData, $rules);

            // The validation should fail due to slug uniqueness constraint
            $this->assertTrue(
                $validator->fails(),
                "Second product creation with duplicate slug '{$uniqueSlug}' should fail validation"
            );

            // Check that the error is specifically about slug uniqueness
            $errors = $validator->errors();
            $this->assertTrue(
                $errors->has('slug'),
                "Validation errors should include 'slug' field for duplicate slug '{$uniqueSlug}'"
            );

            $slugErrors = $errors->get('slug');
            $this->assertNotEmpty(
                $slugErrors,
                "Slug validation errors should not be empty for duplicate slug '{$uniqueSlug}'"
            );

            // Verify the error message indicates the slug already exists
            $errorMessage = implode(' ', $slugErrors);
            $this->assertTrue(
                str_contains($errorMessage, 'đã tồn tại') || str_contains($errorMessage, 'has already been taken'),
                "Error message should indicate that slug '{$uniqueSlug}' already exists. Got: {$errorMessage}"
            );

            // Verify that only one product exists with this slug
            $productsWithSlug = Product::where('slug', $uniqueSlug)->count();
            $this->assertEquals(
                1,
                $productsWithSlug,
                "Only one product should exist with slug '{$uniqueSlug}', found {$productsWithSlug}"
            );

            // Verify the first product is still there and unchanged
            $this->assertDatabaseHas('products', [
                'id' => $firstProduct->id,
                'slug' => $uniqueSlug,
                'name' => 'First Product'
            ]);
        });
    }

    /**
     * Property 2b: Uniqueness constraint works across different product states
     *
     * Verify that slug uniqueness is enforced even when products have different
     * statuses (active/inactive) or other varying attributes.
     *
     */
    public function property_slug_uniqueness_across_different_states(): void
    {
        $this->limitTo(50)->forAll(
            Generator\elements([
                ['active', 'inactive'],
                ['inactive', 'active'],
                ['active', 'active'],
                ['inactive', 'inactive']
            ])
        )->then(function (array $statuses) {
            [$firstStatus, $secondStatus] = $statuses;
            $slug = 'test-product-' . Str::random(8);

            // Create first product with given status
            $firstProduct = Product::factory()->create([
                'slug' => $slug,
                'status' => $firstStatus,
                'name' => 'First Product',
                'api_key' => Str::random(32)
            ]);

            // Try to create second product with same slug but different status
            $secondProductData = [
                'name' => 'Second Product',
                'slug' => $slug,
                'status' => $secondStatus,
                'description' => 'Different description',
                'offline_token_ttl_hours' => 72,
            ];

            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'slug' => [
                    'required',
                    'string',
                    'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
                    'unique:products,slug'
                ],
                'description' => ['nullable', 'string', 'max:1000'],
                'offline_token_ttl_hours' => ['required', 'integer', 'min:1', 'max:168'],
            ];

            $validator = Validator::make($secondProductData, $rules);

            // Should fail regardless of status combination
            $this->assertTrue(
                $validator->fails(),
                "Duplicate slug '{$slug}' should be rejected regardless of status combination: {$firstStatus} -> {$secondStatus}"
            );

            $this->assertTrue(
                $validator->errors()->has('slug'),
                "Validation should fail on slug field for status combination: {$firstStatus} -> {$secondStatus}"
            );

            // Verify only one product exists with this slug
            $this->assertEquals(
                1,
                Product::where('slug', $slug)->count(),
                "Only one product should exist with slug '{$slug}' regardless of status"
            );
        });
    }

    /**
     * Property 2c: Database constraint enforces uniqueness
     *
     * Verify that the database-level unique constraint prevents duplicate slugs,
     * even when trying to create products directly (bypassing validation).
     *
     */
    public function property_database_constraint_enforces_uniqueness(): void
    {
        $this->limitTo(30)->forAll(
            Generator\elements([
                'constraint-test-1',
                'constraint-test-2',
                'constraint-test-3',
                'db-unique-test',
                'database-constraint',
                'unique-enforcement'
            ])
        )->then(function (string $slug) {
            // Create first product
            $firstProduct = Product::factory()->create([
                'slug' => $slug,
                'name' => 'First Product',
                'api_key' => Str::random(32)
            ]);

            $this->assertDatabaseHas('products', [
                'slug' => $slug,
                'name' => 'First Product'
            ]);

            // Try to create a second product with the same slug directly in the database
            // This should fail due to the unique constraint
            $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

            Product::factory()->create([
                'slug' => $slug,
                'name' => 'Second Product',
                'api_key' => Str::random(32)
            ]);
        });
    }

    /**
     * Property 2d: Case sensitivity in slug uniqueness
     *
     * Verify that slug uniqueness is case-sensitive (though all valid slugs should be lowercase).
     *
     */
    public function property_slug_uniqueness_is_case_sensitive(): void
    {
        // Note: This test is more about documenting expected behavior since valid slugs
        // are all lowercase according to the validation pattern
        $slug = 'test-case-sensitivity';

        // Create first product with lowercase slug
        $firstProduct = Product::factory()->create([
            'slug' => $slug,
            'name' => 'First Product',
            'api_key' => Str::random(32)
        ]);

        // Try to create with uppercase version (though this would fail pattern validation anyway)
        $uppercaseSlug = strtoupper($slug);

        $validator = Validator::make([
            'name' => 'Second Product',
            'slug' => $uppercaseSlug,
            'offline_token_ttl_hours' => 24,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
                'unique:products,slug'
            ],
            'offline_token_ttl_hours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);

        // Should fail due to pattern validation (uppercase not allowed)
        $this->assertTrue(
            $validator->fails(),
            "Uppercase slug should fail pattern validation"
        );

        // The pattern validation should fail before uniqueness is even checked
        $errors = $validator->errors();
        $this->assertTrue(
            $errors->has('slug'),
            "Validation should fail on slug field for uppercase slug"
        );
    }
}

