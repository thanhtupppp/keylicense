<?php

namespace App\Actions\Licensing;

use App\Models\Activation;
use App\Models\LicenseKey;
use App\Services\Sdk\Dto\ValidationResult;
use Carbon\CarbonInterface;

class ValidateLicenseAction
{
    public function execute(array $data): ValidationResult
    {
        $license = LicenseKey::query()
            ->with('entitlement.plan.product')
            ->where('license_key', hash('sha256', $data['license_key']))
            ->first();

        if (! $license) {
            return new ValidationResult(false, 'not_found', 'License key not found.', ['message' => 'License key not found.']);
        }

        if (($license->entitlement?->plan?->product?->code ?? null) !== $data['product_code']) {
            return new ValidationResult(false, 'product_mismatch', 'License is not valid for this product.', ['message' => 'License is not valid for this product.']);
        }

        if ($license->status !== 'active') {
            return new ValidationResult(false, 'inactive', 'License is not active.', ['message' => 'License is not active.']);
        }

        if ($license->expires_at instanceof CarbonInterface && $license->expires_at->isPast()) {
            return new ValidationResult(false, 'expired', 'License has expired.', ['message' => 'License has expired.']);
        }

        $activation = null;

        if (! empty($data['activation_id'])) {
            $activation = Activation::query()
                ->where('license_id', $license->id)
                ->where('activation_code', $data['activation_id'])
                ->first();

            if (! $activation) {
                return new ValidationResult(false, 'activation_not_found', 'Activation record not found.', ['message' => 'Activation record not found.']);
            }

            if ($activation->status === 'deactivated') {
                return new ValidationResult(false, 'ACTIVATION_DEACTIVATED', 'Activation is deactivated.', ['message' => 'Activation is deactivated.']);
            }

            if ($activation->status !== 'active') {
                return new ValidationResult(false, 'activation_inactive', 'Activation is not active.', ['message' => 'Activation is not active.']);
            }

            if (! empty($data['domain']) && $activation->domain && $activation->domain !== $data['domain']) {
                return new ValidationResult(false, 'domain_mismatch', 'Activation domain does not match.', ['message' => 'Activation domain does not match.']);
            }

            if (! empty($data['environment']) && $activation->environment && $activation->environment !== $data['environment']) {
                return new ValidationResult(false, 'environment_mismatch', 'Activation environment does not match.', ['message' => 'Activation environment does not match.']);
            }

            $activation->forceFill([
                'last_validated_at' => now(),
            ])->save();
        }

        return new ValidationResult(true, 'valid', 'License is valid.', [
            'license_id' => $license->id,
            'license_status' => $license->status,
            'activation_id' => $activation?->activation_code,
            'expires_at' => $license->expires_at?->toISOString(),
            'validated_at' => now()->toISOString(),
        ]);
    }
}
