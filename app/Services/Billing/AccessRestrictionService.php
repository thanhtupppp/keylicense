<?php

namespace App\Services\Billing;

use App\Models\IpBlocklist;
use App\Models\LicenseIpAllowlist;
use App\Models\PlanGeoRestriction;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessRestrictionService
{
    public function ensureAllowed(?string $licenseId, string $ipAddress, ?string $countryCode = null): void
    {
        if ($this->isBlockedIp($ipAddress)) {
            throw new AccessDeniedHttpException('IP_BLOCKED');
        }

        if ($countryCode !== null && $licenseId !== null && $this->isGeoRestricted($licenseId, $countryCode)) {
            throw new AccessDeniedHttpException('GEO_RESTRICTED');
        }

        if ($licenseId !== null && $this->hasAllowlist($licenseId) && ! $this->isAllowedIp($licenseId, $ipAddress)) {
            throw new AccessDeniedHttpException('IP_NOT_ALLOWED');
        }
    }

    public function isBlockedIp(string $ipAddress): bool
    {
        return IpBlocklist::query()
            ->where('cidr', $ipAddress)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function hasAllowlist(string $licenseId): bool
    {
        return LicenseIpAllowlist::query()->where('license_key_id', $licenseId)->exists();
    }

    public function isAllowedIp(string $licenseId, string $ipAddress): bool
    {
        return LicenseIpAllowlist::query()
            ->where('license_key_id', $licenseId)
            ->where('cidr', $ipAddress)
            ->exists();
    }

    public function isGeoRestricted(string $planId, string $countryCode): bool
    {
        return PlanGeoRestriction::query()
            ->where('plan_id', $planId)
            ->where(function ($query) use ($countryCode): void {
                $query->whereJsonContains('country_codes', $countryCode);
            })
            ->exists();
    }
}
