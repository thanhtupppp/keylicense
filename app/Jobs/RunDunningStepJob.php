<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\Billing\DunningOrchestrator;
use App\Services\Billing\DunningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunDunningStepJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(
        public string $subscriptionId,
        public int $step,
        public int $version = 1,
    ) {
    }

    public function uniqueId(): string
    {
        return "{$this->subscriptionId}:{$this->step}:{$this->version}";
    }

    public function jobId(): string
    {
        return $this->uniqueId();
    }

    public function handle(DunningService $service, DunningOrchestrator $orchestrator): void
    {
        if (! $orchestrator->isScheduleCurrent($this->subscriptionId, $this->version)) {
            return;
        }

        $subscription = Subscription::query()->find($this->subscriptionId);

        if (! $subscription) {
            return;
        }

        $service->runStep($this->step, $subscription->entitlement?->plan?->product_id);
    }
}
