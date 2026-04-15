<?php

namespace App\Services\Billing;

use App\Models\DunningConfig;
use App\Models\DunningLog;
use App\Models\LicenseKey;
use App\Models\Subscription;
use Illuminate\Support\Collection;

class DunningService
{
    /**
     * @return array{processed:int,matched_subscriptions:int}
     */
    public function runStep(int $step, ?string $productId = null): array
    {
        $configs = $this->resolveConfigsByStep($step, $productId);

        if ($configs->isEmpty()) {
            return ['processed' => 0, 'matched_subscriptions' => 0];
        }

        $processed = 0;
        $matchedSubscriptions = 0;

        foreach ($configs as $config) {
            $pastDueFrom = now()->subDays((int) $config->days_after_due);

            $query = Subscription::query()
                ->where('status', 'past_due')
                ->where('updated_at', '<=', $pastDueFrom);

            if ($config->product_id) {
                $query->whereHas('entitlement.plan', static function ($q) use ($config): void {
                    $q->where('product_id', $config->product_id);
                });
            }

            /** @var Collection<int, Subscription> $subscriptions */
            $subscriptions = $query->get();
            $matchedSubscriptions += $subscriptions->count();

            foreach ($subscriptions as $subscription) {
                if ($this->alreadyExecutedStep($subscription->id, (int) $config->step)) {
                    continue;
                }

                $result = $this->executeAction($subscription, $config->action);

                DunningLog::query()->create([
                    'subscription_id' => $subscription->id,
                    'step' => (int) $config->step,
                    'action' => $config->action,
                    'executed_at' => now(),
                    'result' => $result,
                    'notes' => $config->email_template_code
                        ? ('email_template_code=' . $config->email_template_code)
                        : null,
                ]);

                $processed++;
            }
        }

        return [
            'processed' => $processed,
            'matched_subscriptions' => $matchedSubscriptions,
        ];
    }

    public function recoverSubscription(Subscription $subscription): void
    {
        $subscription->loadMissing('entitlement');

        $subscription->forceFill([
            'status' => 'active',
            'cancel_at_period_end' => false,
            'updated_at' => now(),
        ])->save();

        if ($subscription->entitlement && $subscription->entitlement->status === 'suspended') {
            $subscription->entitlement->forceFill([
                'status' => 'active',
                'updated_at' => now(),
            ])->save();
        }

        LicenseKey::query()
            ->where('entitlement_id', $subscription->entitlement_id)
            ->whereIn('status', ['suspended', 'revoked'])
            ->update([
                'status' => 'active',
                'updated_at' => now(),
            ]);

        DunningLog::query()->create([
            'subscription_id' => $subscription->id,
            'step' => 0,
            'action' => 'payment_recovered',
            'executed_at' => now(),
            'result' => 'recovered',
            'notes' => 'Payment recovered. Pending dunning flow should be ignored.',
        ]);
    }

    /**
     * @return Collection<int, DunningConfig>
     */
    private function resolveConfigsByStep(int $step, ?string $productId): Collection
    {
        $query = DunningConfig::query()->where('step', $step);

        if ($productId) {
            $query->where(static function ($q) use ($productId): void {
                $q->where('product_id', $productId)
                    ->orWhereNull('product_id');
            });
        }

        return $query->orderByRaw('product_id IS NULL, product_id')->get();
    }

    private function alreadyExecutedStep(string $subscriptionId, int $step): bool
    {
        return DunningLog::query()
            ->where('subscription_id', $subscriptionId)
            ->where('step', $step)
            ->exists();
    }

    private function executeAction(Subscription $subscription, string $action): string
    {
        if ($action === DunningConfig::ACTION_EMAIL) {
            return 'sent';
        }

        if ($action === DunningConfig::ACTION_SUSPEND) {
            if ($subscription->entitlement && $subscription->entitlement->status !== 'suspended') {
                $subscription->entitlement->forceFill([
                    'status' => 'suspended',
                    'updated_at' => now(),
                ])->save();
            }

            LicenseKey::query()
                ->where('entitlement_id', $subscription->entitlement_id)
                ->where('status', '!=', 'revoked')
                ->update([
                    'status' => 'suspended',
                    'updated_at' => now(),
                ]);

            return 'suspended';
        }

        if ($action === DunningConfig::ACTION_CANCEL) {
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
                ->where('status', '!=', 'revoked')
                ->update([
                    'status' => 'revoked',
                    'updated_at' => now(),
                ]);

            return 'cancelled';
        }

        return 'sent';
    }
}
