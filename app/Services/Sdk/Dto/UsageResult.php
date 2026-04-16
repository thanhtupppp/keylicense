<?php

namespace App\Services\Sdk\Dto;

class UsageResult
{
    public function __construct(
        public readonly bool $recorded,
        public readonly ?int $totalUsage = null,
        public readonly ?bool $overLimit = null,
        public readonly array $payload = []
    ) {
    }
}
