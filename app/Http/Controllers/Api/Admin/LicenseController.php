<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\AdminUser;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Services\Notifications\LicenseNotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function issue(Request $request, LicenseNotificationService $notifications): JsonResponse
    {
        $actor = $this->admin($request);

        if (! $actor) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        if (! $request->user('admin')?->can('admin-license-manage')) {
            return ApiResponse::error('FORBIDDEN', 'Insufficient admin permissions.', 403);
        }

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

            $licenses[] = $this->formatLicense($license, $raw);

            if (filled($entitlement->customer?->email)) {
                $notifications->sendIssuedLicense(
                    $entitlement->customer->email,
                    $raw,
                    $license->key_display,
                    $entitlement
                );
            }
        }

        return ApiResponse::success(['licenses' => $licenses], 201);
    }

    public function history(string $licenseId): JsonResponse
    {
        $license = LicenseKey::query()->findOrFail($licenseId);

        $activations = Activation::query()
            ->where('license_id', $license->id)
            ->orderByDesc('last_validated_at')
            ->get()
            ->map(static fn (Activation $activation): array => [
                'id' => $activation->id,
                'activation_code' => $activation->activation_code,
                'product_code' => $activation->product_code,
                'domain' => $activation->domain,
                'environment' => $activation->environment,
                'status' => $activation->status,
                'activated_at' => $activation->activated_at?->toISOString(),
                'last_validated_at' => $activation->last_validated_at?->toISOString(),
            ])
            ->all();

        return ApiResponse::success([
            'license' => [
                'id' => $license->id,
                'key_display' => $license->key_display,
            ],
            'activations' => $activations,
            'activation_count' => count($activations),
        ]);
    }

    public function revoke(string $id, LicenseNotificationService $notifications): JsonResponse
    {
        return $this->updateStatus($id, 'revoked', $notifications);
    }

    public function suspend(string $id): JsonResponse
    {
        return $this->updateStatus($id, 'suspended');
    }

    public function unsuspend(string $id): JsonResponse
    {
        return $this->updateStatus($id, 'active');
    }

    public function extend(Request $request, string $id): JsonResponse
    {
        $payload = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $license = LicenseKey::query()->findOrFail($id);
        $license->forceFill([
            'expires_at' => $license->expires_at
                ? $license->expires_at->copy()->addDays((int) $payload['days'])
                : now()->addDays((int) $payload['days']),
        ])->save();

        return ApiResponse::success([
            'license' => $this->formatLicense($license->fresh()),
        ]);
    }

    private function updateStatus(string $id, string $status, ?LicenseNotificationService $notifications = null): JsonResponse
    {
        $license = LicenseKey::query()->with('entitlement.customer')->findOrFail($id);
        $license->forceFill(['status' => $status])->save();

        if ($status === 'revoked' && $notifications && filled($license->entitlement?->customer?->email)) {
            $notifications->sendRevokedLicense($license->entitlement->customer->email, $license);
        }

        return ApiResponse::success([
            'license' => $this->formatLicense($license->fresh()),
        ]);
    }

    private function admin(Request $request): ?AdminUser
    {
        $admin = $request->attributes->get('admin_user') ?? $request->user('admin') ?? $request->user();

        return $admin instanceof AdminUser ? $admin : null;
    }

    private function formatLicense(LicenseKey $license, ?string $plainTextKey = null): array
    {
        $data = [
            'id' => $license->id,
            'license_key' => $plainTextKey,
            'key_display' => $license->key_display,
            'status' => $license->status,
            'expires_at' => optional($license->expires_at)?->toISOString(),
        ];

        return array_filter($data, static fn ($value) => $value !== null);
    }
}
