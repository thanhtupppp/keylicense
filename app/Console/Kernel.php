<?php

namespace App\Console;

use App\Console\Commands\DunningRunDailyCommand;
use App\Jobs\DataRetentionCleanupJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(DunningRunDailyCommand::class.' --all')
            ->dailyAt('02:00')
            ->withoutOverlapping();

        $schedule->job(new DataRetentionCleanupJob())
            ->weeklyOn(0, '03:00')
            ->withoutOverlapping();
    }
}
