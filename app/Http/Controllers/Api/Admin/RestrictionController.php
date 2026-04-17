<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpBlocklist;
use App\Models\LicenseIpAllowlist;
use App\Models\PlanGeoRestriction;
use App\Services\Billing\AbuseDetectionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestrictionController extends Controller
{
    public function __construct(private readonly AbuseDetectionService $abuseDetectionService)
    {
    }

    public function licenseAllowlist(string $licenseId): JsonResponse
    {
        return ApiResponse::success([
            'license_id' => $licenseId,
            'entries' => LicenseIpAllowlist::query()->where('license_key_id', $licenseId)->latest()->get(),
        ]);
    }

    public function storeLicenseAllowlist(Request $request, string $licenseId): JsonResponse
    {
        $payload = $request->validate([
            'cidr' => ['required', 'string', 'max:64'],
            'label' => ['nullable', 'string', 'max:128'],
        ]);

        $entry = LicenseIpAllowlist::query()->create([
            'license_key_id' => $licenseId,
            'cidr' => $payload['cidr'],
            'label' => $payload['label'] ?? null,
            'created_by' => null,
        ]);

        return ApiResponse::success([
            'license_id' => $licenseId,
            'entry' => $entry,
        ], 201);
    }

    public function deleteLicenseAllowlist(string $licenseId, string $entryId): JsonResponse
    {
        LicenseIpAllowlist::query()->where('license_key_id', $licenseId)->where('id', $entryId)->delete();

        return ApiResponse::success([
            'license_id' => $licenseId,
            'entry_id' => $entryId,
            'deleted' => true,
        ]);
    }

    public function blocklist(): JsonResponse
    {
        return ApiResponse::success([
            'entries' => IpBlocklist::query()->latest()->get(),
        ]);
    }

    public function storeBlocklist(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'cidr' => ['required', 'string', 'max:64', 'unique:ip_blocklist,cidr'],
            'reason' => ['nullable', 'string', 'max:128'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $entry = IpBlocklist::query()->create([
            'cidr' => $payload['cidr'],
            'reason' => $payload['reason'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
            'created_by' => null,
        ]);

        return ApiResponse::success([
            'entry' => $entry,
        ], 201);
    }

    public function deleteBlocklist(string $id): JsonResponse
    {
        IpBlocklist::query()->whereKey($id)->delete();

        return ApiResponse::success([
            'entry_id' => $id,
            'deleted' => true,
        ]);
    }

    public function planGeoRestrictions(string $planId): JsonResponse
    {
        return ApiResponse::success([
            'plan_id' => $planId,
            'entries' => PlanGeoRestriction::query()->where('plan_id', $planId)->latest()->get(),
        ]);
    }

    public function upsertPlanGeoRestrictions(Request $request, string $planId): JsonResponse
    {
        $payload = $request->validate([
            'restriction_type' => ['required', Rule::in(['allowlist', 'blocklist'])],
            'country_codes' => ['required', 'array', 'min:1'],
            'country_codes.*' => ['string', 'size:2'],
        ]);

        $entry = PlanGeoRestriction::query()->create([
            'plan_id' => $planId,
            'restriction_type' => $payload['restriction_type'],
            'country_codes' => $payload['country_codes'],
        ]);

        return ApiResponse::success([
            'plan_id' => $planId,
            'entry' => $entry,
        ], 201);
    }

    public function detect(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'subscription_id' => ['required', 'uuid'],
            'license_id' => ['required', 'uuid'],
            'ip' => ['required', 'string', 'max:64'],
            'country_code' => ['required', 'string', 'size:2'],
        ]);

        $blocked = $this->abuseDetectionService->isIpBlocked($payload['ip'])
            || ! $this->abuseDetectionService->isLicenseIpAllowed($payload['license_id'], $payload['ip']);

        $allowedCountry = $this->abuseDetectionService->isCountryAllowed($payload['subscription_id'], $payload['country_code']);

        return ApiResponse::success([
            'blocked' => $blocked || ! $allowedCountry,
            'country_allowed' => $allowedCountry,
        ]);
    }
}
