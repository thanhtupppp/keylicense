<?php

namespace App\Services\Sdk\Dto;

class ChallengeResult
{
    public function __construct(
        public readonly bool $issued,
        public readonly ?string $challengeId = null,
        public readonly ?string $expiresAt = null,
        public readonly array $payload = []
    ) {
    }
}
