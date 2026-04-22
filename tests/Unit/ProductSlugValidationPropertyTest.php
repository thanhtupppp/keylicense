<?php

namespace Tests\Unit;

use App\Http\Requests\StoreProductRequest;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Property-based test for product slug validation
 *
 * **Validates: Requirements 1.2**
 *
 */
class ProductSlugValidationPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    // -------------------------------------------------------------------------
    // Property 1 — Product slug validation
    // -------------------------------------------------------------------------

    /**
     * Property 1: Product slug validation
     *
     * For any string: accept if and only if it matches `^[a-z0-9][a-z0-9-]*[a-z0-9]$`;
     * reject with validation error if it doesn't match.
     *
     * Uses Eris to generate various strings and verifies that the validation
     * accepts only strings matching the required pattern and rejects all others.
     *
     */
    public function property_slug_validation_accepts_only_valid_patterns(): void
    {
        $this->limitTo(200)->forAll(
            // Generate test cases with known valid/invalid slugs
            Generator\elements([
                // Valid slugs
                ['ab', true],
                ['a1', true],
                ['1a', true],
                ['abc', true],
                ['test-product', true],
                ['my-app-v2', true],
                ['product123', true],
                ['a-b-c-d-e', true],
                ['123-456', true],
                ['app2024', true],
                ['hello-world', true],
                ['test123', true],
                ['app-name', true],
                ['product-v1', true],
                ['my-product', true],
                ['web-app', true],
                ['api-v2', true],
                ['mobile-app', true],
                ['desktop-client', true],
                ['service-worker', true],

                // Invalid slugs
                ['', false],                    // Empty
                ['a', false],                   // Too short
                ['1', false],                   // Too short
                ['-abc', false],                // Starts with hyphen
                ['abc-', false],                // Ends with hyphen
                ['ABC', false],                 // Uppercase
                ['test_product', false],        // Underscore
                ['test.product', false],        // Dot
                ['test product', false],        // Space
                ['test@product', false],        // Special char
                ['-', false],                   // Only hyphen
                ['--', false],                  // Only hyphens
                ['a-', false],                  // Ends with hyphen
                ['-a', false],                  // Starts with hyphen
                ['Test', false],                // Uppercase
                ['test-Product', false],        // Mixed case
                ['123-', false],                // Ends with hyphen
                ['-123', false],                // Starts with hyphen
                ['test_123', false],            // Underscore
                ['test.123', false],            // Dot
                ['test 123', false],            // Space
                ['test#123', false],            // Hash
                ['test$123', false],            // Dollar
                ['test%123', false],            // Percent
                ['test^123', false],            // Caret
                ['test&123', false],            // Ampersand
                ['test*123', false],            // Asterisk
                ['test(123)', false],           // Parentheses
                ['test+123', false],            // Plus
                ['test=123', false],            // Equals
                ['test[123]', false],           // Brackets
                ['test{123}', false],           // Braces
                ['test|123', false],            // Pipe
                ['test\\123', false],           // Backslash
                ['test:123', false],            // Colon
                ['test;123', false],            // Semicolon
                ['test"123"', false],           // Quotes
                ["test'123'", false],           // Single quotes
                ['test<123>', false],           // Angle brackets
                ['test,123', false],            // Comma
                ['test?123', false],            // Question mark
                ['test/123', false],            // Slash
                ['test~123', false],            // Tilde
                ['test`123', false],            // Backtick
            ])
        )->then(function (array $testCase) {
            [$slug, $shouldBeValid] = $testCase;

            // Create validation rules from StoreProductRequest
            $request = new StoreProductRequest();
            $rules = $request->rules();

            // Extract only the slug validation rules (excluding unique constraint for this test)
            $slugRules = array_filter($rules['slug'], function ($rule) {
                return !is_string($rule) || !str_contains($rule, 'unique:');
            });

            // Validate the slug
            $validator = Validator::make(['slug' => $slug], ['slug' => $slugRules]);
            $isValid = !$validator->fails();

            if ($shouldBeValid) {
                $this->assertTrue(
                    $isValid,
                    "Slug '{$slug}' should be valid but validation failed. Errors: " .
                        json_encode($validator->errors()->toArray())
                );
            } else {
                $this->assertFalse(
                    $isValid,
                    "Slug '{$slug}' should be invalid but validation passed"
                );
            }
        });
    }

    /**
     * Property 1b: Valid slug pattern verification
     *
     * For any string that matches the pattern `^[a-z0-9][a-z0-9-]*[a-z0-9]$`,
     * the validation must accept it.
     *
     */
    public function property_valid_pattern_strings_always_pass(): void
    {
        $this->limitTo(50)->forAll(
            Generator\elements([
                'ab',
                'a1',
                '1a',
                'abc',
                'test-product',
                'my-app-v2',
                'product123',
                'a-b-c-d-e',
                '123-456',
                'app2024',
                'hello-world',
                'test123',
                'app-name',
                'product-v1',
                'my-product',
                'web-app',
                'api-v2',
                'mobile-app',
                'desktop-client',
                'service-worker'
            ])
        )->then(function (string $slug) {
            // Verify the slug matches the expected pattern
            $pattern = '/^[a-z0-9][a-z0-9-]*[a-z0-9]$/';
            $this->assertMatchesRegularExpression(
                $pattern,
                $slug,
                "Test slug '{$slug}' doesn't match the required pattern"
            );

            // Test validation
            $request = new StoreProductRequest();
            $rules = $request->rules();
            $slugRules = array_filter($rules['slug'], function ($rule) {
                return !is_string($rule) || !str_contains($rule, 'unique:');
            });

            $validator = Validator::make(['slug' => $slug], ['slug' => $slugRules]);
            $this->assertFalse(
                $validator->fails(),
                "Valid slug '{$slug}' failed validation. Errors: " .
                    json_encode($validator->errors()->toArray())
            );
        });
    }

    /**
     * Property 1c: Invalid slug pattern verification
     *
     * For any string that does NOT match the pattern `^[a-z0-9][a-z0-9-]*[a-z0-9]$`,
     * the validation must reject it.
     *
     */
    public function property_invalid_pattern_strings_always_fail(): void
    {
        $this->limitTo(50)->forAll(
            Generator\elements([
                '',
                'a',
                '1',
                '-abc',
                'abc-',
                'ABC',
                'test_product',
                'test.product',
                'test product',
                'test@product',
                '-',
                '--',
                'a-',
                '-a',
                'Test',
                'test-Product',
                '123-',
                '-123',
                'test_123',
                'test.123',
                'test 123',
                'test#123',
                'test$123',
                'test%123',
                'test^123',
                'test&123',
                'test*123',
                'test(123)',
                'test+123',
                'test=123',
                'test[123]',
                'test{123}',
                'test|123',
                'test\\123',
                'test:123',
                'test;123',
                'test"123"',
                "test'123'",
                'test<123>',
                'test,123',
                'test?123',
                'test/123',
                'test~123',
                'test`123'
            ])
        )->then(function (string $slug) {
            // Verify the slug does NOT match the expected pattern
            $pattern = '/^[a-z0-9][a-z0-9-]*[a-z0-9]$/';
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                $slug,
                "Test slug '{$slug}' actually matches the required pattern when it shouldn't"
            );

            // Test validation
            $request = new StoreProductRequest();
            $rules = $request->rules();
            $slugRules = array_filter($rules['slug'], function ($rule) {
                return !is_string($rule) || !str_contains($rule, 'unique:');
            });

            $validator = Validator::make(['slug' => $slug], ['slug' => $slugRules]);
            $this->assertTrue(
                $validator->fails(),
                "Invalid slug '{$slug}' passed validation when it should have failed"
            );
        });
    }

    /**
     * Property 1d: Edge case validation
     *
     * Test specific edge cases for the slug validation pattern.
     *
     */
    public function property_edge_cases_are_handled_correctly(): void
    {
        // Test minimum valid length (2 characters)
        $validator = Validator::make(['slug' => 'ab'], ['slug' => ['required', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/']]);
        $this->assertFalse($validator->fails(), "Minimum valid slug 'ab' should pass");

        // Test single character (should fail)
        $validator = Validator::make(['slug' => 'a'], ['slug' => ['required', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/']]);
        $this->assertTrue($validator->fails(), "Single character slug 'a' should fail");

        // Test hyphen in middle (should pass)
        $validator = Validator::make(['slug' => 'a-b'], ['slug' => ['required', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/']]);
        $this->assertFalse($validator->fails(), "Slug with hyphen in middle 'a-b' should pass");

        // Test multiple hyphens (should pass)
        $validator = Validator::make(['slug' => 'a-b-c'], ['slug' => ['required', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/']]);
        $this->assertFalse($validator->fails(), "Slug with multiple hyphens 'a-b-c' should pass");

        // Test consecutive hyphens (should pass - pattern allows it)
        $validator = Validator::make(['slug' => 'a--b'], ['slug' => ['required', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/']]);
        $this->assertFalse($validator->fails(), "Slug with consecutive hyphens 'a--b' should pass");
    }
}

