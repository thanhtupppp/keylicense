<?php

namespace App\Contracts;

interface LicenseStateContract
{
    /**
     * Determine if the license can be activated.
     */
    public function canActivate(): bool;

    /**
     * Determine if the license can be suspended.
     */
    public function canSuspend(): bool;

    /**
     * Determine if the license can be revoked.
     */
    public function canRevoke(): bool;

    /**
     * Determine if the license can be restored.
     */
    public function canRestore(): bool;

    /**
     * Determine if the license can be renewed.
     */
    public function canRenew(): bool;

    /**
     * Determine if the license can be un-revoked.
     */
    public function canUnrevoke(): bool;
}
