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
     * @return array<int, array{step:int,delay_minutes:int,product_id:?string}>
     */
    public function scheduleForPastDue(string $subscriptionId, ?string $productId = null): array
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

            Bus::dispatch((new RunDunningStepJob((int) $config->step, $productId))->delay(now()->addMinutes($delayMinutes)));

            $scheduled[] = [
                'step' => (int) $config->step,
                'delay_minutes' => $delayMinutes,
                'product_id' => $productId,
            ];
        }

        return $scheduled;
    }
}
