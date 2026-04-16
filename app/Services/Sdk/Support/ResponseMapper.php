<?php

namespace App\Services\Sdk\Support;

use App\Services\Sdk\Dto\ActivationResult;
use App\Services\Sdk\Dto\ChallengeResult;
use App\Services\Sdk\Dto\CouponResult;
use App\Services\Sdk\Dto\HeartbeatResult;
use App\Services\Sdk\Dto\UpdateResult;
use App\Services\Sdk\Dto\UsageResult;
use App\Services\Sdk\Dto\ValidationResult;

class ResponseMapper
{
    public function activation(array $payload): ActivationResult
    {
        return new ActivationResult(
            (bool) data_get($payload, 'data.success', true),
            data_get($payload, 'data.activation_id'),
            data_get($payload, 'data.status'),
            $payload,
        );
    }

    public function validation(array $payload): ValidationResult
    {
        return new ValidationResult(
            (bool) data_get($payload, 'data.valid', false),
            data_get($payload, 'data.status'),
            data_get($payload, 'data.message'),
            $payload,
        );
    }

    public function heartbeat(array $payload): HeartbeatResult
    {
        return new HeartbeatResult(
            (bool) data_get($payload, 'data.accepted', true),
            data_get($payload, 'data.next_heartbeat_at'),
            $payload,
        );
    }

    public function update(array $payload): UpdateResult
    {
        return new UpdateResult(
            (bool) data_get($payload, 'data.update_available', false),
            data_get($payload, 'data.version'),
            data_get($payload, 'data.download_url'),
            $payload,
        );
    }

    public function challenge(array $payload): ChallengeResult
    {
        return new ChallengeResult(
            (bool) data_get($payload, 'data.issued', true),
            data_get($payload, 'data.challenge_id'),
            data_get($payload, 'data.expires_at'),
            $payload,
        );
    }

    public function usage(array $payload): UsageResult
    {
        return new UsageResult(
            (bool) data_get($payload, 'data.recorded', true),
            data_get($payload, 'data.total_usage'),
            data_get($payload, 'data.over_limit'),
            $payload,
        );
    }

    public function coupon(array $payload, string $couponCode, string $planCode): CouponResult
    {
        return new CouponResult(
            (bool) data_get($payload, 'data.valid', false),
            data_get($payload, 'data.coupon_code', $couponCode),
            data_get($payload, 'data.plan_code', $planCode),
            $payload,
        );
    }
}
