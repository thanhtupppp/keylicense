<?php

namespace App\Services\Sdk\Dto;

class HeartbeatResult
{
    public function __construct(
        public readonly bool $accepted,
        public readonly ?string $nextHeartbeatAt = null,
        public readonly array $payload = []
    ) {
    }
}
