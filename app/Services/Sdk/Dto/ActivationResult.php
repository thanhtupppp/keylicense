<?php

namespace App\Services\Sdk\Dto;

class ActivationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $activationId = null,
        public readonly ?string $licenseStatus = null,
        public readonly array $payload = []
    ) {
    }
}
