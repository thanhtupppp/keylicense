<?php

namespace App\Jobs;

use App\Services\Privacy\DataRetentionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DataRetentionCleanupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function handle(DataRetentionService $service): void
    {
        $service->applyRetention('dunning_logs', now()->subDays(90));
        $service->applyRetention('notification_logs', now()->subDays(180));
    }
}
