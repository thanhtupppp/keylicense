<?php

namespace App\Services\Sdk\Support;

class SdkConfig
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly string $apiKey,
        public readonly string $productCode,
        public readonly string $environment = 'production',
        public readonly int $timeout = 10,
        public readonly RetryPolicy $retry = new RetryPolicy(),
        public readonly string $cacheDriver = 'file',
        public readonly ?string $cachePath = null,
        public readonly int $cacheTtl = 86400,
        public readonly ?string $logChannel = 'stderr',
    ) {
    }
}
