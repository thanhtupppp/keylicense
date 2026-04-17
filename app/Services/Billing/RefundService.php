<?php

namespace App\Services\Billing;

use App\Models\LicenseKey;
use App\Models\Subscription;

class RefundService
{
    public function revokeForSubscription(Subscription $subscription, string $reason = 'refund'): void
    {
        $subscription->forceFill([
            'status' => 'cancelled',
            'cancel_at_period_end' => true,
            'updated_at' => now(),
        ])->save();

        if ($subscription->entitlement) {
            $subscription->entitlement->forceFill([
                'status' => 'cancelled',
                'updated_at' => now(),
            ])->save();
        }

        LicenseKey::query()
            ->where('entitlement_id', $subscription->entitlement_id)
            ->update([
                'status' => $reason === 'chargeback' ? 'revoked' : 'revoked',
                'updated_at' => now(),
            ]);
    }
}
