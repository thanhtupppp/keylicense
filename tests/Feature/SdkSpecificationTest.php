<?php

use App\Services\Sdk\LicensePlatformClient;
use App\Services\Sdk\Exceptions\LicensePlatformException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

test('sdk client maps success responses for core flows', function (): void {
    Http::fake([
        'license.test/v1/client/licenses/activate' => Http::response(['data' => ['activation_id' => 'act-1', 'status' => 'active']], 200),
        'license.test/v1/client/licenses/validate' => Http::response(['data' => ['valid' => true, 'status' => 'active']], 200),
        'license.test/v1/client/licenses/heartbeat' => Http::response(['data' => ['accepted' => true, 'next_heartbeat_at' => now()->addMinutes(15)->toISOString()]], 200),
        'license.test/v1/client/licenses/update-check' => Http::response(['data' => ['update_available' => false]], 200),
        'license.test/v1/client/licenses/offline/challenge' => Http::response(['data' => ['issued' => true, 'challenge_id' => 'ch-1', 'expires_at' => now()->addHour()->toISOString()]], 200),
        'license.test/v1/client/licenses/offline/confirm' => Http::response(['data' => ['activation_id' => 'act-2', 'status' => 'active']], 200),
        'license.test/v1/client/usage/records' => Http::response(['data' => ['recorded' => true, 'total_usage' => 5, 'over_limit' => false]], 200),
        'license.test/v1/client/coupons/validate' => Http::response(['data' => ['valid' => true, 'coupon_code' => 'WELCOME10', 'plan_code' => 'pro']], 200),
    ]);

    $client = new LicensePlatformClient([
        'base_url' => 'https://license.test',
        'api_key' => 'test-key',
        'product_code' => 'PLUGIN_SEO',
        'timeout' => 5,
        'retry_attempts' => 2,
    ]);

    expect($client->activate('LIC-1', 'example.com', ['device_id' => 'dev-1'])->activationId)->toBe('act-1');
    expect($client->validate('LIC-1', 'act-1', 'example.com')->valid)->toBeTrue();
    expect($client->heartbeat('act-1', 'LIC-1', 'example.com')->accepted)->toBeTrue();
    expect($client->checkUpdate('LIC-1', '1.0.0', 'example.com')->updateAvailable)->toBeFalse();
    expect($client->requestOfflineChallenge('LIC-1', 'example.com', ['device_id' => 'dev-1'])->challengeId)->toBe('ch-1');
    expect($client->recordUsage('LIC-1', 'api_calls', 5, 'idem-1')->recorded)->toBeTrue();
    expect($client->validateCoupon('WELCOME10', 'pro')->valid)->toBeTrue();
});

test('sdk client sends idempotency and correlation headers', function (): void {
    Http::fake([
        'license.test/v1/client/licenses/activate' => Http::response(['data' => ['activation_id' => 'act-1', 'status' => 'active']], 200),
        'license.test/v1/client/licenses/heartbeat' => Http::response(['data' => ['accepted' => true]], 200),
        'license.test/v1/client/licenses/update-check' => Http::response(['data' => ['update_available' => false]], 200),
        'license.test/v1/client/licenses/offline/challenge' => Http::response(['data' => ['issued' => true, 'challenge_id' => 'ch-1']], 200),
        'license.test/v1/client/coupons/validate' => Http::response(['data' => ['valid' => true, 'coupon_code' => 'WELCOME10', 'plan_code' => 'pro']], 200),
    ]);

    $client = new LicensePlatformClient([
        'base_url' => 'https://license.test',
        'api_key' => 'test-key',
        'product_code' => 'PLUGIN_SEO',
    ]);

    $client->activate('LIC-1', 'example.com', ['device_id' => 'dev-1']);
    $client->heartbeat('act-1', 'LIC-1', 'example.com');
    $client->checkUpdate('LIC-1', '1.0.0', 'example.com');
    $client->requestOfflineChallenge('LIC-1', 'example.com', ['device_id' => 'dev-1']);
    $client->validateCoupon('WELCOME10', 'pro');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://license.test/v1/client/licenses/activate'
            && $request->hasHeader('Idempotency-Key');
    });

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://license.test/v1/client/licenses/heartbeat'
            && $request->hasHeader('X-Correlation-Id');
    });
});

test('sdk client maps api errors to typed exception', function (): void {
    Http::fake([
        'license.test/v1/client/licenses/activate' => Http::response(['error_code' => 'LICENSE_NOT_FOUND', 'message' => 'License not found.'], 403),
    ]);

    $client = new LicensePlatformClient([
        'base_url' => 'https://license.test',
        'api_key' => 'test-key',
        'product_code' => 'PLUGIN_SEO',
    ]);

    $this->expectException(LicensePlatformException::class);
    $this->expectExceptionMessage('License not found.');

    $client->activate('LIC-404', 'example.com', []);
});
