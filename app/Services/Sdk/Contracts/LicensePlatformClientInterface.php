<?php

namespace App\Services\Sdk\Contracts;

use App\Services\Sdk\Dto\ActivationResult;
use App\Services\Sdk\Dto\ChallengeResult;
use App\Services\Sdk\Dto\CouponResult;
use App\Services\Sdk\Dto\HeartbeatResult;
use App\Services\Sdk\Dto\UpdateResult;
use App\Services\Sdk\Dto\UsageResult;
use App\Services\Sdk\Dto\ValidationResult;

interface LicensePlatformClientInterface
{
    public function activate(string $licenseKey, string $domain, array $device): ActivationResult;

    public function validate(string $licenseKey, string $activationId, string $domain): ValidationResult;

    public function heartbeat(string $activationId, string $licenseKey, string $domain): HeartbeatResult;

    public function deactivate(string $activationId, string $licenseKey, string $reason): bool;

    public function checkUpdate(string $licenseKey, string $currentVersion, string $domain): UpdateResult;

    public function requestOfflineChallenge(string $licenseKey, string $domain, array $device): ChallengeResult;

    public function confirmOfflineActivation(string $challengeId, string $responseToken): ActivationResult;

    public function recordUsage(string $licenseKey, string $metricCode, int $quantity, string $idempotencyKey): UsageResult;

    public function validateCoupon(string $couponCode, string $planCode): CouponResult;
}
