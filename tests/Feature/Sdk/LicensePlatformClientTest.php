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

    $client = new LicensePlatformClient([
        'base_url' => 'https://example.test',
        'api_key' => 'api-key',
        'product_code' => 'prod-1',
    ]);

    $response = $client->activate('raw-key', 'example.com');

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

    $client = new LicensePlatformClient([
        'base_url' => 'https://example.test',
        'product_code' => 'prod-1',
    ]);

    $response = $client->validate('raw-key', 'act_123', 'example.com');

    expect($response->ok)->toBeFalse()
        ->and($response->status)->toBe(403)
        ->and($response->code)->toBe('LICENSE_EXPIRED');
});

it('supports heartbeat, deactivate and update check', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [
                'accepted' => true,
                'next_heartbeat_at' => now()->addHours(12)->toISOString(),
                'update_available' => true,
            ],
        ], 200),
    ]);

    $client = new LicensePlatformClient([
        'base_url' => 'https://example.test',
        'product_code' => 'prod-1',
    ]);

    $heartbeat = $client->heartbeat('act_123', 'raw-key', 'example.com');
    $update = $client->updateCheck('raw-key', '1.0.0', 'example.com');
    $deactivate = $client->deactivate('raw-key', 'act_123', 'example.com');

    expect($heartbeat->ok)->toBeTrue()
        ->and($update->ok)->toBeTrue()
        ->and($deactivate->ok)->toBeTrue();
});
