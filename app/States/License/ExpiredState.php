<?php

namespace App\States\License;

class ExpiredState extends LicenseState
{
    protected static string $name = 'expired';

    public function canActivate(): bool
    {
        return false;
    }

    public function canSuspend(): bool
    {
        return false;
    }

    public function canRevoke(): bool
    {
        return false;
    }

    public function canRestore(): bool
    {
        return false;
    }

    public function canRenew(): bool
    {
        return true;
    }

    public function canUnrevoke(): bool
    {
        return false;
    }
}
