<?php

namespace App\States\License;

class ActiveState extends LicenseState
{
    protected static string $name = 'active';

    public function canActivate(): bool
    {
        return false;
    }

    public function canSuspend(): bool
    {
        return true;
    }

    public function canRevoke(): bool
    {
        return true;
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
