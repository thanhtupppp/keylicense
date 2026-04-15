<?php

namespace Tests\Feature\Concerns;

use App\Models\DunningConfig;

final class DunningAssertions
{
    public static function reportStructure(): array
    {
        return [
            'data' => [
                'report' => [
                    'from',
                    'to',
                    'total_actions',
                    'recovered_count',
                    'cancelled_count',
                    'suspended_count',
                    'recovery_rate_percent',
                ],
                'by_product',
                'by_subscription',
            ],
        ];
    }

    public static function logPayload(int $subscriptionId, int $step, string $result, string $action = DunningConfig::ACTION_EMAIL): array
    {
        return [
            'subscription_id' => $subscriptionId,
            'step' => $step,
            'action' => $action,
            'result' => $result,
        ];
    }

    public static function recoveredSubscriptionPayload(int $subscriptionId): array
    {
        return [
            'id' => $subscriptionId,
            'status' => 'active',
            'cancel_at_period_end' => false,
        ];
    }
}
