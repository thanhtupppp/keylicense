<?php

namespace App\Jobs;

use App\Services\Billing\DunningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunDunningStepJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * @param int $step 1..N
     */
    public function __construct(
        public int $step,
        public ?string $productId = null,
    ) {
    }

    public function handle(DunningService $service): void
    {
        $service->runStep($this->step, $this->productId);
    }
}
