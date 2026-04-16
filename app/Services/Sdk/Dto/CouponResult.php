<?php

namespace App\Services\Sdk\Dto;

class CouponResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $couponCode = null,
        public readonly ?string $planCode = null,
        public readonly array $payload = []
    ) {
    }
}
