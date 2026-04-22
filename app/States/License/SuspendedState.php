<?php

namespace App\States\License;

class SuspendedState extends LicenseState
{
    protected static string $name = 'suspended';

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
        return true;
    }

    public function canRestore(): bool
    {
        return true;
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
