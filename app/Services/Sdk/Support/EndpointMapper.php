<?php

namespace App\Services\Sdk\Support;

class EndpointMapper
{
    private const MAP = [
        'activate' => '/v1/client/licenses/activate',
        'validate' => '/v1/client/licenses/validate',
        'heartbeat' => '/v1/client/licenses/heartbeat',
        'deactivate' => '/v1/client/licenses/deactivate',
        'checkUpdate' => '/v1/client/licenses/update-check',
        'requestOfflineChallenge' => '/v1/client/licenses/offline/challenge',
        'confirmOfflineActivation' => '/v1/client/licenses/offline/confirm',
        'recordUsage' => '/v1/client/usage/records',
        'validateCoupon' => '/v1/client/coupons/validate',
    ];

    public function resolve(string $method): string
    {
        return self::MAP[$method] ?? throw new \InvalidArgumentException("Unknown SDK endpoint method: {$method}");
    }

    public function path(string $method): string
    {
        return $this->resolve($method);
    }
}
