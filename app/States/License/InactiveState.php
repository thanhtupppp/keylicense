<?php

namespace App\States\License;

class InactiveState extends LicenseState
{
    protected static string $name = 'inactive';

    public function canActivate(): bool
    {
        return true;
    }

    public function canSuspend(): bool
    {
        return false;
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
        return false;
    }

    public function canUnrevoke(): bool
    {
        return false;
    }
}
