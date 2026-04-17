<?php

namespace App\Console;

use App\Console\Commands\Licensing\RunGracePeriodSweepCommand;
use App\Jobs\ExpireGracePeriodActivationsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        RunGracePeriodSweepCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new ExpireGracePeriodActivationsJob())
            ->dailyAt('02:00')
            ->withoutOverlapping();
    }
}
