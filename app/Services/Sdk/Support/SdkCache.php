<?php

namespace App\Services\Sdk\Support;

use Illuminate\Support\Facades\Cache;

class SdkCache
{
    public function remember(string $key, int $ttl, callable $resolver): mixed
    {
        return Cache::remember($key, $ttl, $resolver);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
