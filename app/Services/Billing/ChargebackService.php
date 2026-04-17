<?php

namespace App\Services\Billing;

use App\Models\Subscription;

class ChargebackService
{
    public function reverse(Subscription $subscription, string $reason = 'chargeback'): void
    {
        app(RefundService::class)->revokeForSubscription($subscription, $reason);
    }
}
