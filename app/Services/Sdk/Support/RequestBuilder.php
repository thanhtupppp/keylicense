<?php

namespace App\Services\Sdk\Support;

use Illuminate\Support\Str;

class RequestBuilder
{
    public function __construct(private SdkConfig $config)
    {
    }

    public function headers(array $extra = []): array
    {
        return [
            'Authorization' => 'Bearer '.$this->config->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-License-Platform-Product' => $this->config->productCode,
            'X-License-Platform-Environment' => $this->config->environment,
            ...$extra,
        ];
    }

    public function idempotencyHeaders(?string $key = null, array $extra = []): array
    {
        return $this->headers(array_merge([
            'Idempotency-Key' => $key ?? (string) Str::uuid(),
        ], $extra));
    }

    public function correlationHeaders(?string $correlationId = null, array $extra = []): array
    {
        return $this->headers(array_merge([
            'X-Correlation-Id' => $correlationId ?? (string) Str::uuid(),
        ], $extra));
    }
}
