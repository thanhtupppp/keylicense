<?php

namespace App\Support\Licensing;

final class LicenseActivationStates
{
    public const ACTIVE = 'active';
    public const GRACE_PERIOD = 'grace_period';
    public const EXPIRED = 'expired';
    public const DEACTIVATED = 'deactivated';
    public const PENDING = 'pending';

    public static function isTerminal(string $state): bool
    {
        return \in_array($state, [self::EXPIRED, self::DEACTIVATED], true);
    }
}
