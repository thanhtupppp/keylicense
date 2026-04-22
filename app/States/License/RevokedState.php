<?php

namespace App\States\License;

class RevokedState extends LicenseState
{
    protected static string $name = 'revoked';

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
        return false;
    }

    public function canUnrevoke(): bool
    {
        return true;
    }
}
