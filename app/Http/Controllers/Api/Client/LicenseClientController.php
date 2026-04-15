<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\LicenseKey;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LicenseClientController extends Controller
{
    public function activate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'license_key' => ['required', 'string'],
            'product_code' => ['required', 'string'],
            'domain' => ['required', 'string'],
            'app_version' => ['nullable', 'string'],
            'environment' => ['nullable', 'string'],
            'device' => ['nullable', 'array'],
        ]);

        $license = LicenseKey::query()
            ->with(['entitlement.plan'])
            ->where('license_key', hash('sha256', $payload['license_key']))
            ->first();

        if (! $license) {
            return ApiResponse::error('LICENSE_NOT_FOUND', 'License key not found.', 403);
        }

        if (! in_array($license->status, ['issued', 'active'], true)) {
            return ApiResponse::error('LICENSE_INVALID', 'License status is not valid.', 403);
        }

        if ($license->expires_at && now()->greaterThan($license->expires_at)) {
            return ApiResponse::error('LICENSE_EXPIRED', 'License key has expired.', 403);
        }

        $planLimit = $license->entitlement->max_activations
            ?? $license->entitlement->plan->max_activations;

        $currentActivations = Activation::query()
            ->where('license_id', $license->id)
            ->where('status', 'active')
            ->count();

        if ($planLimit !== null && $currentActivations >= $planLimit) {
            return ApiResponse::error('ACTIVATION_LIMIT_EXCEEDED', 'Activation limit exceeded.', 403);
        }

        $activation = Activation::query()->create([
            'activation_code' => 'act_'.Str::lower(Str::random(16)),
            'license_id' => $license->id,
            'product_code' => $payload['product_code'],
            'domain' => $payload['domain'],
            'environment' => $payload['environment'] ?? 'production',
            'status' => 'active',
            'activated_at' => now(),
            'last_validated_at' => now(),
        ]);

        $license->update(['status' => 'active']);

        return ApiResponse::success([
            'activation_id' => $activation->activation_code,
            'status' => 'active',
            'license' => [
                'key_display' => $license->key_display,
                'product_code' => $payload['product_code'],
                'plan_code' => $license->entitlement->plan->code,
                'expires_at' => optional($license->expires_at)?->toISOString(),
                'max_activations' => $planLimit,
                'current_activations' => $currentActivations + 1,
            ],
            'policy' => [
                'offline_allowed' => true,
                'grace_period_days' => 3,
                'features' => (object) [],
            ],
            'token' => [
                'value' => Str::random(80),
                'expires_at' => now()->addHours(6)->toISOString(),
            ],
        ]);
    }

    public function validateLicense(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'license_key' => ['required', 'string'],
            'product_code' => ['required', 'string'],
            'activation_id' => ['required', 'string'],
            'domain' => ['required', 'string'],
            'app_version' => ['nullable', 'string'],
        ]);

        $license = LicenseKey::query()
            ->with('entitlement.plan')
            ->where('license_key', hash('sha256', $payload['license_key']))
            ->first();

        if (! $license) {
            return ApiResponse::error('LICENSE_NOT_FOUND', 'License key not found.', 403);
        }

        $activation = Activation::query()
            ->where('license_id', $license->id)
            ->where('activation_code', $payload['activation_id'])
            ->where('domain', $payload['domain'])
            ->first();

        if (! $activation) {
            return ApiResponse::error('ACTIVATION_NOT_FOUND', 'Activation not found.', 403);
        }

        if ($license->expires_at && now()->greaterThan($license->expires_at)) {
            return ApiResponse::error('LICENSE_EXPIRED', 'License key has expired.', 403);
        }

        $activation->update(['last_validated_at' => now()]);

        return ApiResponse::success([
            'valid' => true,
            'status' => $activation->status,
            'expires_at' => optional($license->expires_at)?->toISOString(),
            'features' => (object) [],
            'message' => null,
        ]);
    }
}
