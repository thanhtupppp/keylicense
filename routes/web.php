<?php

use App\Http\Controllers\Web\AdminPortalAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::prefix('admin')->name('admin.portal.')->group(function (): void {
    Route::get('/login', [AdminPortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminPortalAuthController::class, 'login'])->name('login.submit');

    Route::middleware('admin.portal.auth')->group(function (): void {
        Route::get('/dashboard', [AdminPortalAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('/sessions', [AdminPortalAuthController::class, 'sessions'])->name('sessions');
        Route::post('/sessions/revoke', [AdminPortalAuthController::class, 'revokeSession'])->name('sessions.revoke');
        Route::post('/sessions/revoke-others', [AdminPortalAuthController::class, 'revokeAllExceptCurrent'])->name('sessions.revoke_others');
        Route::post('/logout', [AdminPortalAuthController::class, 'logout'])->name('logout');
    });
});
