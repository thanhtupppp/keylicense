<?php

namespace App\Services\Billing;

use App\Models\IpBlocklist;
use App\Models\LicenseIpAllowlist;
use App\Models\PlanGeoRestriction;
use App\Models\Subscription;
use Illuminate\Support\Str;

class AbuseDetectionService
{
    public function isIpBlocked(string $ip): bool
    {
        return IpBlocklist::query()
            ->where('cidr', $ip)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->exists();
    }

    public function isLicenseIpAllowed(string $licenseId, string $ip): bool
    {
        $allowlist = LicenseIpAllowlist::query()->where('license_key_id', $licenseId)->pluck('cidr')->all();

        if ($allowlist === []) {
            return true;
        }

        return \in_array($ip, $allowlist, true);
    }

    public function isCountryAllowed(string $planId, string $countryCode): bool
    {
        $restriction = PlanGeoRestriction::query()->where('plan_id', $planId)->first();

        if (! $restriction) {
            return true;
        }

        $countryCode = Str::upper($countryCode);
        $allowed = array_map('strtoupper', $restriction->country_codes ?? []);

        return $restriction->restriction_type === 'allowlist'
            ? \in_array($countryCode, $allowed, true)
            : ! \in_array($countryCode, $allowed, true);
    }

    public function flagSubscription(Subscription $subscription, string $reason): void
    {
        $subscription->forceFill([
            'status' => 'past_due',
            'metadata' => array_merge($subscription->metadata ?? [], [
                'abuse_flag' => $reason,
            ]),
            'updated_at' => now(),
        ])->save();
    }
}
