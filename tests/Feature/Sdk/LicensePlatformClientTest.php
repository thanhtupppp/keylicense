<?php

use App\Services\Sdk\LicensePlatformClient;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

it('maps successful sdk responses', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [
                'status' => 'active',
                'activation_id' => 'act_123',
            ],
        ], 200),
    ]);

    $client = new LicensePlatformClient('https://example.test', 'api-key');
    $response = $client->activate([
        'license_key' => 'raw-key',
        'product_code' => 'prod-1',
        'domain' => 'example.com',
    ]);

    expect($response->ok)->toBeTrue()
        ->and($response->status)->toBe(200)
        ->and($response->data['status'])->toBe('active');
});

it('maps failed sdk responses', function (): void {
    Http::fake([
        '*' => Http::response([
            'code' => 'LICENSE_EXPIRED',
            'message' => 'License key has expired.',
        ], 403),
    ]);

    $client = new LicensePlatformClient('https://example.test');
    $response = $client->validate([
        'license_key' => 'raw-key',
        'product_code' => 'prod-1',
        'activation_id' => 'act_123',
        'domain' => 'example.com',
    ]);

    expect($response->ok)->toBeFalse()
        ->and($response->status)->toBe(403)
        ->and($response->code)->toBe('LICENSE_EXPIRED');
});
