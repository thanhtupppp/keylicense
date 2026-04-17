<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use Carbon\CarbonInterface;

class RenewalService
{
    public function renew(Subscription $subscription, CarbonInterface|null $currentPeriodEnd = null): Subscription
    {
        $currentPeriodEnd ??= now()->addMonth();

        $subscription->forceFill([
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => $currentPeriodEnd,
            'cancel_at_period_end' => false,
            'updated_at' => now(),
        ])->save();

        if ($subscription->entitlement) {
            $subscription->entitlement->forceFill([
                'status' => 'active',
                'expires_at' => $currentPeriodEnd,
                'updated_at' => now(),
            ])->save();
        }

        return $subscription->fresh(['entitlement']);
    }
}
