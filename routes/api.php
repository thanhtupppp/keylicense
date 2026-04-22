<?php

use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\PublicKeyController;
use Illuminate\Support\Facades\Route;

// Public endpoint (no auth required, but with json_response middleware)
Route::middleware(['json_response'])->group(function () {
    Route::get('/v1/public-key', [PublicKeyController::class, 'show']);
});

// API routes with authentication and rate limiting
Route::prefix('v1')->middleware(['api_key', 'rate_limit_api_key', 'json_response'])->group(function () {
    // License endpoints
    Route::post('/licenses/activate', [LicenseController::class, 'activate']);
    Route::post('/licenses/validate', [LicenseController::class, 'validate']);
    Route::post('/licenses/deactivate', [LicenseController::class, 'deactivate']);
    Route::get('/licenses/info', [LicenseController::class, 'info']);
    Route::post('/licenses/transfer', [LicenseController::class, 'transfer']);

    // Heartbeat endpoint
    Route::post('/licenses/heartbeat', [HeartbeatController::class, 'heartbeat']);
});
