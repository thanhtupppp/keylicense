<?php

namespace App\Services\Sdk\Dto;

class ValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $status = null,
        public readonly ?string $message = null,
        public readonly array $payload = []
    ) {
    }
}
