<?php

use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\AdminPortalSessionAuth;
use App\Http\Middleware\ClientApiKeyMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => AdminAuthMiddleware::class,
            'client.api-key' => ClientApiKeyMiddleware::class,
            'admin.portal.auth' => AdminPortalSessionAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            return null;
        });
    })->create();
