<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DunningOrchestrator
{
    private const MANIFEST_TTL_DAYS = 30;

    public function __construct(private readonly DunningScheduler $scheduler)
    {
    }

    public function handlePaymentFailed(Subscription $subscription): void
    {
        $subscription->forceFill([
            'status' => 'past_due',
            'cancel_at_period_end' => false,
            'updated_at' => now(),
        ])->save();

        $manifest = $this->buildManifest($subscription, $this->nextVersion($subscription->id));
        $this->storeManifest($subscription->id, $manifest);
        $this->scheduler->scheduleManifest($manifest);
    }

    public function handlePaymentSucceeded(Subscription $subscription): void
    {
        $this->invalidateSchedule($subscription->id);
        app(DunningService::class)->recoverSubscription($subscription);
    }

    public function retryPayment(Subscription $subscription): void
    {
        $this->invalidateSchedule($subscription->id);
        app(DunningService::class)->recoverSubscription($subscription);
    }

    public function isScheduleCurrent(string $subscriptionId, int $version): bool
    {
        $manifest = $this->manifest($subscriptionId);

        return (int) ($manifest['version'] ?? 0) === $version && ($manifest['state'] ?? null) === 'scheduled';
    }

    public function manifest(string $subscriptionId): array
    {
        $manifest = Cache::get($this->manifestKey($subscriptionId));

        return \is_array($manifest) ? $manifest : [];
    }

    public function cancelPending(string $subscriptionId, string $reason = 'recovered'): void
    {
        $manifest = $this->manifest($subscriptionId);

        if ($manifest !== []) {
            $manifest['state'] = 'cancelled';
            $manifest['cancelled_at'] = now()->toISOString();
            $manifest['cancel_reason'] = $reason;
            $this->storeManifest($subscriptionId, $manifest);
        }
    }

    /**
     * @return array{subscription_id:string,product_id:?string,version:int,state:string,steps:array<int,array{job_id:string,step:int,delay_minutes:int,product_id:?string}>,created_at:string}
     */
    private function buildManifest(Subscription $subscription, int $version): array
    {
        return [
            'subscription_id' => $subscription->id,
            'product_id' => $subscription->entitlement?->plan?->product_id,
            'version' => $version,
            'state' => 'scheduled',
            'steps' => $this->scheduler->stepsForSubscription(
                subscriptionId: $subscription->id,
                productId: $subscription->entitlement?->plan?->product_id,
                version: $version,
            ),
            'created_at' => now()->toISOString(),
        ];
    }

    private function storeManifest(string $subscriptionId, array $manifest): void
    {
        Cache::put($this->manifestKey($subscriptionId), $manifest, now()->addDays(self::MANIFEST_TTL_DAYS));
    }

    private function nextVersion(string $subscriptionId): int
    {
        $manifest = $this->manifest($subscriptionId);
        $current = (int) ($manifest['version'] ?? 0);

        return $current + 1;
    }

    private function invalidateSchedule(string $subscriptionId): void
    {
        $manifest = $this->manifest($subscriptionId);

        if ($manifest !== []) {
            $manifest['state'] = 'invalidated';
            $manifest['invalidated_at'] = now()->toISOString();
            $this->storeManifest($subscriptionId, $manifest);
        }
    }

    private function manifestKey(string $subscriptionId): string
    {
        return 'dunning:schedule:'.Str::lower($subscriptionId);
    }
}
