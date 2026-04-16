<?php

uses(Tests\TestCase::class);

use App\Services\Sdk\Support\RequestBuilder;
use App\Services\Sdk\Support\ResponseMapper;
use App\Services\Sdk\Support\RetryPolicy;
use App\Services\Sdk\Support\SdkConfig;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

test('request builder adds auth and product headers', function (): void {
    $builder = new RequestBuilder(new SdkConfig(
        baseUrl: 'https://license.test',
        apiKey: 'test-key',
        productCode: 'PLUGIN_SEO',
        environment: 'production',
        timeout: 10,
        retry: new RetryPolicy(),
        cacheDriver: 'file',
        cachePath: null,
        cacheTtl: 86400,
        logChannel: 'stderr',
    ));

    $headers = $builder->headers();

    expect($headers['Authorization'])->toBe('Bearer test-key');
    expect($headers['X-License-Platform-Product'])->toBe('PLUGIN_SEO');
    expect($headers['X-License-Platform-Environment'])->toBe('production');
});

test('request builder adds idempotency and correlation headers', function (): void {
    $builder = new RequestBuilder(new SdkConfig(
        baseUrl: 'https://license.test',
        apiKey: 'test-key',
        productCode: 'PLUGIN_SEO',
        environment: 'production',
        timeout: 10,
        retry: new RetryPolicy(),
        cacheDriver: 'file',
        cachePath: null,
        cacheTtl: 86400,
        logChannel: 'stderr',
    ));

    $idempotencyHeaders = $builder->idempotencyHeaders('idem-123');
    $correlationHeaders = $builder->correlationHeaders('corr-123');

    expect($idempotencyHeaders['Idempotency-Key'])->toBe('idem-123');
    expect($correlationHeaders['X-Correlation-Id'])->toBe('corr-123');
});

test('response mapper hydrates dto objects', function (): void {
    $mapper = new ResponseMapper();

    $activation = $mapper->activation(['data' => ['activation_id' => 'act-1', 'status' => 'active']]);
    $validation = $mapper->validation(['data' => ['valid' => true, 'status' => 'active', 'message' => null]]);
    $heartbeat = $mapper->heartbeat(['data' => ['accepted' => true, 'next_heartbeat_at' => '2026-04-16T12:00:00Z']]);
    $update = $mapper->update(['data' => ['update_available' => false]]);
    $challenge = $mapper->challenge(['data' => ['issued' => true, 'challenge_id' => 'ch-1']]);
    $usage = $mapper->usage(['data' => ['recorded' => true, 'total_usage' => 10, 'over_limit' => false]]);
    $coupon = $mapper->coupon(['data' => ['valid' => true, 'coupon_code' => 'WELCOME10', 'plan_code' => 'pro']], 'WELCOME10', 'pro');

    expect($activation->activationId)->toBe('act-1');
    expect($validation->valid)->toBeTrue();
    expect($heartbeat->nextHeartbeatAt)->toBe('2026-04-16T12:00:00Z');
    expect($update->updateAvailable)->toBeFalse();
    expect($challenge->challengeId)->toBe('ch-1');
    expect($usage->totalUsage)->toBe(10);
    expect($coupon->couponCode)->toBe('WELCOME10');
});

