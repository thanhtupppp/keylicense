<?php

namespace App\Services\Sdk\Dto;

class UpdateResult
{
    public function __construct(
        public readonly bool $updateAvailable,
        public readonly ?string $version = null,
        public readonly ?string $downloadUrl = null,
        public readonly array $payload = []
    ) {
    }
}
