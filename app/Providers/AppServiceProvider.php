<?php

namespace App\Providers;

use App\Support\AuditLogger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, fn () => new AuditLogger());
    }

    public function boot(): void
    {
        //
    }
}
