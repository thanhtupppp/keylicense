<?php

uses(Tests\TestCase::class);

use App\Services\Sdk\Support\SdkCache;
use Illuminate\Support\Facades\Cache;

it('remembers values through the cache layer', function (): void {
    Cache::shouldReceive('remember')
        ->once()
        ->with('sdk:test', 60, Mockery::type('callable'))
        ->andReturn('cached-value');

    $cache = new SdkCache();

    expect($cache->remember('sdk:test', 60, fn () => 'fresh-value'))->toBe('cached-value');
});

it('forgets cached values through the cache layer', function (): void {
    Cache::shouldReceive('forget')
        ->once()
        ->with('sdk:test')
        ->andReturnTrue();

    $cache = new SdkCache();

    expect($cache->forget('sdk:test'))->toBeNull();
});

