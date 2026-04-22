<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Laravel Scheduler Configuration
Schedule::command('licenses:check-expired')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('heartbeats:cleanup')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('audit-logs:archive')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();
