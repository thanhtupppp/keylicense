<?php

namespace Tests\Support;

use App\Models\IpBlocklist;
use App\Models\LicenseIpAllowlist;
use App\Models\PlanGeoRestriction;
use Illuminate\Support\Str;

final class AbuseDetectionFixtures
{
    public static function blockIp(string $ip): void
    {
        IpBlocklist::query()->create([
            'id' => (string) Str::uuid(),
            'cidr' => $ip,
            'reason' => 'test',
            'expires_at' => now()->addDay(),
            'created_by' => null,
        ]);
    }

    public static function allowLicenseIp(string $licenseId, string $ip): void
    {
        LicenseIpAllowlist::query()->create([
            'id' => (string) Str::uuid(),
            'license_key_id' => $licenseId,
            'cidr' => $ip,
            'label' => 'test',
            'created_by' => null,
        ]);
    }

    public static function setGeoRestriction(string $planId, string $type, array $countries): void
    {
        PlanGeoRestriction::query()->create([
            'id' => (string) Str::uuid(),
            'plan_id' => $planId,
            'restriction_type' => $type,
            'country_codes' => $countries,
        ]);
    }
}
