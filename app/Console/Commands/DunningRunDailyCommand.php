<?php

namespace App\Console\Commands;

use App\Jobs\RunDunningStepJob;
use App\Services\Billing\DunningScheduler;
use Illuminate\Console\Command;

class DunningRunDailyCommand extends Command
{
    protected $signature = 'dunning:run-daily {--all : Dispatch steps for all products and global configs} {--product_id= : Dispatch steps for a specific product} {--sync : Run synchronously without queue}';

    protected $description = 'Dispatch configured daily dunning steps';

    public function handle(DunningScheduler $scheduler): int
    {
        $productId = $this->option('product_id') ?: null;

        if (! $this->option('all') && $productId === null) {
            $this->error('Specify --all or --product_id.');

            return self::FAILURE;
        }

        $steps = $scheduler->stepsFor($productId);

        if (empty($steps)) {
            $this->warn('No dunning configuration found.');

            return self::SUCCESS;
        }

        foreach ($steps as $step) {
            if ($this->option('sync')) {
                RunDunningStepJob::dispatchSync($step, $productId ?: null);
                continue;
            }

            RunDunningStepJob::dispatch($step, $productId ?: null);
        }

        $this->info('Dunning configured jobs have been dispatched.');

        return self::SUCCESS;
    }
}
