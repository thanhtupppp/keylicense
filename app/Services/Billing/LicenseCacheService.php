<?php

namespace App\Services\Billing;

use App\Models\Activation;
use App\Models\LicenseKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class LicenseCacheService
{
    public function licenseStatus(string $licenseHash): array
    {
        return $this->rememberJson("license:{$licenseHash}", 300, function () use ($licenseHash): array {
            $license = LicenseKey::query()
                ->with(['entitlement.plan'])
                ->where('license_key', $licenseHash)
                ->first();

            return $license ? [
                'found' => true,
                'license_id' => $license->id,
                'status' => $license->status,
                'expires_at' => $license->expires_at?->toISOString(),
                'plan_id' => $license->entitlement?->plan?->id,
            ] : ['found' => false];
        });
    }

    public function policy(string $licenseId): array
    {
        return $this->rememberJson("policy:{$licenseId}", 3600, function () use ($licenseId): array {
            $license = LicenseKey::query()->with(['entitlement.plan'])->find($licenseId);

            return [
                'license_id' => $licenseId,
                'max_activations' => $license?->entitlement?->max_activations ?? $license?->entitlement?->plan?->max_activations,
                'max_sites' => $license?->entitlement?->max_sites ?? $license?->entitlement?->plan?->max_sites,
            ];
        });
    }

    public function activation(string $activationId): array
    {
        return $this->rememberJson("activation:{$activationId}", 300, function () use ($activationId): array {
            $activation = Activation::query()->find($activationId);

            return $activation ? [
                'found' => true,
                'activation_id' => $activation->id,
                'license_id' => $activation->license_id,
                'status' => $activation->status,
                'last_validated_at' => $activation->last_validated_at?->toISOString(),
            ] : ['found' => false];
        });
    }

    public function invalidateLicense(string $licenseHash, string $licenseId, ?string $activationId = null): void
    {
        $keys = ["license:{$licenseHash}", "policy:{$licenseId}"];

        if ($activationId) {
            $keys[] = "activation:{$activationId}";
        }

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public function invalidateLicenseById(string $licenseId): void
    {
        Cache::forget("policy:{$licenseId}");
    }

    public function invalidateActivation(string $activationId): void
    {
        Cache::forget("activation:{$activationId}");
    }

    private function rememberJson(string $key, int $ttlSeconds, callable $resolver): array
    {
        $lock = Cache::lock("lock:{$key}", 10);

        return $lock->block(3, function () use ($key, $ttlSeconds, $resolver): array {
            $cached = Cache::get($key);

            if (is_array($cached)) {
                return $cached;
            }

            $resolved = $resolver();
            Cache::put($key, $resolved, $ttlSeconds);

            return $resolved;
        });
    }
}
