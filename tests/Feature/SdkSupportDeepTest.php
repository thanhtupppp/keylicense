<?php

uses(Tests\TestCase::class);

use App\Services\Sdk\Support\EndpointMapper;
use App\Services\Sdk\Support\RetryPolicy;
use App\Services\Sdk\Support\SdkConfig;

it('resolves sdk endpoints by method name', function (): void {
    $mapper = new EndpointMapper();

    expect($mapper->resolve('activate'))->toBe('/v1/client/licenses/activate');
    expect($mapper->resolve('validate'))->toBe('/v1/client/licenses/validate');
    expect($mapper->resolve('recordUsage'))->toBe('/v1/client/usage/records');
});

it('resolves sdk endpoint aliases through path helper', function (): void {
    $mapper = new EndpointMapper();

    expect($mapper->path('activate'))->toBe('/v1/client/licenses/activate');
    expect($mapper->path('validateCoupon'))->toBe('/v1/client/coupons/validate');
});

it('throws on unknown sdk endpoint method', function (): void {
    $mapper = new EndpointMapper();

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Unknown SDK endpoint method');

    $mapper->resolve('unknownMethod');
});

it('keeps sdk config values intact', function (): void {
    $config = new SdkConfig(
        baseUrl: 'https://license.test',
        apiKey: 'test-key',
        productCode: 'PLUGIN_SEO',
        environment: 'staging',
        timeout: 15,
        retry: new RetryPolicy(maxAttempts: 3, delays: [100, 250, 500]),
        cacheDriver: 'redis',
        cachePath: '/tmp/cache',
        cacheTtl: 600,
        logChannel: null,
    );

    expect($config->baseUrl)->toBe('https://license.test');
    expect($config->environment)->toBe('staging');
    expect($config->retry->maxAttempts)->toBe(3);
    expect($config->cacheDriver)->toBe('redis');
    expect($config->cacheTtl)->toBe(600);
    expect($config->logChannel)->toBeNull();
});

it('exposes laravel retry delays', function (): void {
    $policy = new RetryPolicy(maxAttempts: 4, delays: [100, 200, 400]);

    expect($policy->maxAttempts)->toBe(4);
    expect($policy->laravelRetry())->toBe([100, 200, 400]);
});

