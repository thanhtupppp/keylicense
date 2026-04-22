<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\LicenseController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\AdminProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login');
});

// Public routes can be added here later, without affecting admin.

// Admin authentication routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminLoginController::class, 'login'])->middleware('throttle:5,15');
    Route::post('logout', [AdminLoginController::class, 'logout'])->name('logout');
});

// Admin profile
Route::prefix('admin')->name('admin.')->middleware(['admin.auth'])->group(function () {
    Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
});

// Protected admin routes
Route::prefix('admin')->name('admin.')->middleware(['admin.auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/audit-logs', function () {
        return view('admin.audit-logs');
    })->name('audit-logs');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');

    Route::resource('products', ProductController::class);
    Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
        ->name('products.toggle-status');

    Route::get('licenses/export', [LicenseController::class, 'export'])->name('licenses.export');
    Route::get('licenses/batch-created', [LicenseController::class, 'batchCreated'])->name('licenses.batch-created');
    Route::resource('licenses', LicenseController::class)->except(['edit', 'update', 'destroy']);

    Route::post('licenses/{license}/revoke', [LicenseController::class, 'revoke'])->middleware('throttle:10,1')->name('licenses.revoke');
    Route::post('licenses/{license}/suspend', [LicenseController::class, 'suspend'])->middleware('throttle:10,1')->name('licenses.suspend');
    Route::post('licenses/{license}/restore', [LicenseController::class, 'restore'])->middleware('throttle:10,1')->name('licenses.restore');
    Route::post('licenses/{license}/renew', [LicenseController::class, 'renew'])->middleware('throttle:10,1')->name('licenses.renew');
    Route::post('licenses/{license}/unrevoke', [LicenseController::class, 'unrevoke'])->middleware('throttle:10,1')->name('licenses.unrevoke');
    Route::post('licenses/{license}/revoke-activation', [LicenseController::class, 'revokeActivation'])->middleware('throttle:10,1')->name('licenses.revoke-activation');
});
