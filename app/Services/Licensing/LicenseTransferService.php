<?php

namespace App\Services\Licensing;

use App\Models\LicenseKey;
use App\Models\LicenseTransfer;

class LicenseTransferService
{
    public function transfer(LicenseKey $licenseKey, ?string $fromCustomerId, ?string $toCustomerId, string $reason = 'transfer'): LicenseTransfer
    {
        $transfer = LicenseTransfer::query()->create([
            'license_key_id' => $licenseKey->id,
            'from_customer_id' => $fromCustomerId,
            'to_customer_id' => $toCustomerId,
            'status' => 'completed',
            'reason' => $reason,
            'transferred_at' => now(),
            'metadata' => [
                'auto_revoke_activations' => true,
            ],
        ]);

        $licenseKey->forceFill([
            'status' => 'active',
            'updated_at' => now(),
        ])->save();

        $licenseKey->activations()->update([
            'status' => 'deactivated',
            'updated_at' => now(),
        ]);

        return $transfer;
    }
}
