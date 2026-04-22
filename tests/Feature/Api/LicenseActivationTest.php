<?php

namespace Tests\Feature\Api;

use App\Models\License;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_endpoint_requires_api_key(): void
    {
        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => 'TEST-1234-5678-ABCD',
            'device_fingerprint' => 'test-device-fp',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                ],
            ]);
    }

    public function test_activate_endpoint_rejects_invalid_api_key(): void
    {
        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => 'TEST-1234-5678-ABCD',
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => 'invalid-api-key',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                ],
            ]);
    }

    public function test_activate_endpoint_validates_license_key_format(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => 'invalid-format',
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);
    }

    public function test_activate_endpoint_rejects_inactive_product(): void
    {
        $product = Product::factory()->create([
            'status' => 'inactive',
        ]);

        $licenseKey = 'TEST-1234-5678-ABCD';
        $keyHash = hash('sha256', $licenseKey);

        License::factory()->create([
            'product_id' => $product->id,
            'key_hash' => $keyHash,
            'key_last4' => 'ABCD',
            'license_model' => 'per-device',
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => $licenseKey,
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_INACTIVE',
                ],
            ]);
    }

    public function test_activate_endpoint_returns_license_not_found_for_nonexistent_key(): void
    {
        $product = Product::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => 'TEST-1234-5678-ABCD',
            'device_fingerprint' => 'test-device-fp',
        ], [
            'X-API-Key' => $product->api_key,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'LICENSE_NOT_FOUND',
                ],
            ]);
    }

    public function test_public_key_endpoint_does_not_require_api_key(): void
    {
        $response = $this->getJson('/api/v1/public-key');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'algorithm' => 'RS256',
                ],
            ]);
    }

    public function test_response_includes_x_request_id_header(): void
    {
        $response = $this->getJson('/api/v1/public-key');

        $response->assertHeader('X-Request-ID');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->headers->get('X-Request-ID')
        );
    }

    public function test_response_includes_content_type_json_header(): void
    {
        $response = $this->getJson('/api/v1/public-key');

        $response->assertHeader('Content-Type', 'application/json');
    }
}
