<?php

namespace App\Console\Commands;

use App\Jobs\RunDunningStepJob;
use Illuminate\Console\Command;

class DunningRunCommand extends Command
{
    protected $signature = 'dunning:run {step : Dunning step number} {--product_id=} {--sync : Run synchronously without queue}';

    protected $description = 'Run one dunning step workflow';

    public function handle(): int
    {
        $step = (int) $this->argument('step');

        if ($step < 1 || $step > 10) {
            $this->error('Step must be between 1 and 10.');

            return self::FAILURE;
        }

        $productId = $this->option('product_id');

        if ($this->option('sync')) {
            RunDunningStepJob::dispatchSync($step, $productId ?: null);
            $this->info('Dunning step executed synchronously.');

            return self::SUCCESS;
        }

        RunDunningStepJob::dispatch($step, $productId ?: null);
        $this->info('Dunning step has been queued.');

        return self::SUCCESS;
    }
}
