<?php

namespace App\Actions\Licensing\Activation;

use App\Models\Activation;
use App\Models\LicenseKey;
use App\Services\Sdk\Dto\ActivationResult;
use Illuminate\Support\Str;

class ActivateLicenseAction
{
    public function execute(array $data): ActivationResult
    {
        $license = LicenseKey::query()
            ->with('entitlement.plan.product')
            ->where('license_key', hash('sha256', $data['license_key']))
            ->first();

        if (! $license) {
            return new ActivationResult(false, null, 'not_found', 'License key not found.', ['message' => 'License key not found.']);
        }

        if (($license->entitlement?->plan?->product?->code ?? null) !== $data['product_code']) {
            return new ActivationResult(false, null, 'product_mismatch', 'License is not valid for this product.', ['message' => 'License is not valid for this product.']);
        }

        if ($license->status !== 'active') {
            return new ActivationResult(false, null, 'inactive', 'License is not active.', ['message' => 'License is not active.']);
        }

        $activation = Activation::query()->updateOrCreate(
            [
                'license_id' => $license->id,
                'domain' => $data['domain'] ?? null,
                'environment' => $data['environment'] ?? null,
            ],
            [
                'activation_code' => $data['activation_id'] ?? (string) Str::uuid(),
                'product_code' => $data['product_code'],
                'status' => 'active',
                'activated_at' => now(),
                'last_validated_at' => now(),
            ]
        );

        return new ActivationResult(true, $activation->activation_code, 'active', 'License activated.', [
            'license_id' => $license->id,
            'license_status' => $license->status,
            'expires_at' => $license->expires_at?->toISOString(),
            'activated_at' => $activation->activated_at?->toISOString(),
            'validated_at' => $activation->last_validated_at?->toISOString(),
        ]);
    }
}
