<?php

namespace App\Services\Billing;

use App\Jobs\RunDunningStepJob;
use App\Models\DunningConfig;
use Illuminate\Support\Facades\Bus;

class DunningScheduler
{
    /**
     * @return array<int, int>
     */
    public function stepsFor(?string $productId = null): array
    {
        return DunningConfig::query()
            ->when($productId, static fn ($query) => $query->where(function ($q) use ($productId): void {
                $q->where('product_id', $productId)->orWhereNull('product_id');
            }))
            ->orderBy('step')
            ->distinct()
            ->pluck('step')
            ->map(static fn ($step): int => (int) $step)
            ->values()
            ->all();
    }

    /**
     * @param array{subscription_id:string,product_id:?string,version:int,state:string,steps:array<int,array{job_id:string,step:int,delay_minutes:int,product_id:?string}>,created_at:string} $manifest
     * @return array<int, array{job_id:string,step:int,delay_minutes:int,product_id:?string}>
     */
    public function scheduleManifest(array $manifest): array
    {
        $scheduled = [];

        foreach ($manifest['steps'] as $step) {
            $job = new RunDunningStepJob(
                subscriptionId: $manifest['subscription_id'],
                step: (int) $step['step'],
                version: (int) $manifest['version'],
            );

            Bus::dispatch($job->delay(now()->addMinutes((int) $step['delay_minutes'])));

            $scheduled[] = $step;
        }

        return $scheduled;
    }

    /**
     * @return array<int, array{job_id:string,step:int,delay_minutes:int,product_id:?string}>
     */
    public function stepsForSubscription(string $subscriptionId, ?string $productId = null, int $version = 1): array
    {
        $configs = DunningConfig::query()
            ->when($productId, static fn ($query) => $query->where(function ($q) use ($productId): void {
                $q->where('product_id', $productId)->orWhereNull('product_id');
            }))
            ->orderBy('step')
            ->get();

        $scheduled = [];

        foreach ($configs as $config) {
            $delayMinutes = ((int) $config->days_after_due) * 24 * 60;
            $job = new RunDunningStepJob($subscriptionId, (int) $config->step, $version);

            $scheduled[] = [
                'job_id' => $job->jobId(),
                'step' => (int) $config->step,
                'delay_minutes' => $delayMinutes,
                'product_id' => $productId,
            ];
        }

        return $scheduled;
    }
}
