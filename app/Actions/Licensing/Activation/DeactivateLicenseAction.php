<?php

namespace App\Actions\Licensing\Activation;

use App\Models\Activation;
use App\Models\LicenseKey;
use App\Services\Sdk\Dto\ActivationResult;

class DeactivateLicenseAction
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

        $activationQuery = Activation::query()->where('license_id', $license->id);

        if (! empty($data['activation_id'])) {
            $activationQuery->where('activation_code', $data['activation_id']);
        }

        $activation = $activationQuery->first();

        if (! $activation) {
            return new ActivationResult(false, null, 'activation_not_found', 'Activation record not found.', ['message' => 'Activation record not found.']);
        }

        $activation->forceFill([
            'status' => 'deactivated',
        ])->save();

        return new ActivationResult(true, $activation->activation_code, 'deactivated', 'License deactivated.', [
            'license_id' => $license->id,
            'deactivated_at' => now()->toISOString(),
        ]);
    }
}
