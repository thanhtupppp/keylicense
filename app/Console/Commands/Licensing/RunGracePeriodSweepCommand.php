<?php

namespace App\Console\Commands\Licensing;

use App\Jobs\ExpireGracePeriodActivationsJob;
use Illuminate\Console\Command;

class RunGracePeriodSweepCommand extends Command
{
    protected $signature = 'licensing:grace-period:sweep';

    protected $description = 'Expire activations whose grace period has elapsed.';

    public function handle(): int
    {
        ExpireGracePeriodActivationsJob::dispatchSync();

        $this->info('Grace period sweep completed.');

        return self::SUCCESS;
    }
}
