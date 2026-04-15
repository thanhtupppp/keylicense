<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\Billing\DunningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecoverDunningSubscriptionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public string $subscriptionId)
    {
    }

    public function handle(DunningService $service): void
    {
        $subscription = Subscription::query()->find($this->subscriptionId);

        if (! $subscription) {
            return;
        }

        $service->recoverSubscription($subscription);
    }
}
