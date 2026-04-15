<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function issue(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'entitlement_id' => ['required', 'uuid', 'exists:entitlements,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'note' => ['nullable', 'string'],
        ]);

        $entitlement = Entitlement::query()->findOrFail($payload['entitlement_id']);
        $licenses = [];

        for ($i = 0; $i < $payload['quantity']; $i++) {
            $raw = sprintf(
                'PROD1-%s-%s-%s',
                strtoupper(Str::random(5)),
                strtoupper(Str::random(5)),
                strtoupper(Str::random(5))
            );

            $license = LicenseKey::query()->create([
                'entitlement_id' => $entitlement->id,
                'license_key' => hash('sha256', $raw),
                'key_display' => sprintf('PROD1-****-****-%s', substr($raw, -5)),
                'status' => 'issued',
                'expires_at' => $entitlement->expires_at,
            ]);

            $licenses[] = [
                'id' => $license->id,
                'license_key' => $raw,
                'key_display' => $license->key_display,
                'status' => $license->status,
                'expires_at' => optional($license->expires_at)?->toISOString(),
            ];
        }

        return ApiResponse::success(['licenses' => $licenses], 201);
    }
}
