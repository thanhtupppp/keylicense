<?php

use App\Http\Controllers\Web\AdminPortalAuthController;
use App\Http\Controllers\Web\AdminPortalController;
use App\Http\Controllers\Web\AdminPortalLicenseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::prefix('client')->name('client.')->group(function (): void {
    Route::get('/portal', fn () => view('client.portal'))->name('portal');
    Route::get('/portal/licenses', fn () => view('client.licenses'))->name('licenses');
    Route::get('/portal/licenses/{id}', fn ($id) => view('client.license-detail', ['licenseId' => $id]))->name('license-detail');
    Route::get('/portal/invoices', fn () => view('client.invoices'))->name('invoices');
    Route::get('/portal/subscriptions', fn () => view('client.subscriptions'))->name('subscriptions');
    Route::get('/portal/notifications', fn () => view('client.notifications'))->name('notifications');
    Route::get('/portal/profile', fn () => view('client.profile'))->name('profile');
    Route::get('/portal/auth', fn () => view('client.auth'))->name('auth');
    Route::get('/portal/gdpr', fn () => view('client.gdpr'))->name('gdpr');
    Route::get('/portal/activate', fn () => view('client.activate'))->name('activate');
    Route::get('/portal/validate', fn () => view('client.validate'))->name('validate');
    Route::get('/portal/offline', fn () => view('client.offline'))->name('offline');
    Route::get('/portal/deactivate', fn () => view('client.deactivate'))->name('deactivate');
});

Route::prefix('admin')->name('admin.portal.')->group(function (): void {
    Route::get('/login', [AdminPortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminPortalAuthController::class, 'login'])->name('login.submit');

    Route::middleware('admin.portal.auth')->group(function (): void {
        Route::get('/dashboard', [AdminPortalAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('/sessions', [AdminPortalAuthController::class, 'sessions'])->name('sessions');
        Route::post('/sessions/revoke', [AdminPortalAuthController::class, 'revokeSession'])->name('sessions.revoke');
        Route::post('/sessions/revoke-others', [AdminPortalAuthController::class, 'revokeAllExceptCurrent'])->name('sessions.revoke_others');
        Route::post('/logout', [AdminPortalAuthController::class, 'logout'])->name('logout');

        Route::get('/billing', fn () => view('admin.billing'))->name('billing');
        Route::get('/settings', fn () => view('admin.settings'))->name('settings');
        Route::get('/api-keys', fn () => view('admin.api-keys'))->name('api-keys');
        Route::get('/coupons', fn () => view('admin.coupons'))->name('coupons');
        Route::get('/licenses', [AdminPortalController::class, 'licenses'])->name('licenses');
        Route::get('/licenses/{id}', [AdminPortalLicenseController::class, 'detail'])->name('licenses.detail');
        Route::post('/licenses/{id}/revoke', [AdminPortalLicenseController::class, 'revoke'])->name('licenses.revoke');
        Route::post('/licenses/{id}/suspend', [AdminPortalLicenseController::class, 'suspend'])->name('licenses.suspend');
        Route::post('/licenses/{id}/extend', [AdminPortalLicenseController::class, 'extend'])->name('licenses.extend');
        Route::get('/entitlements/{id}', [AdminPortalLicenseController::class, 'entitlementDetail'])->name('entitlements.detail');
        Route::get('/invoices', fn () => view('admin.invoices'))->name('invoices');
        Route::get('/invoices/{id}', [AdminPortalLicenseController::class, 'invoiceDetail'])->name('invoice-detail');
        Route::post('/invoices/{id}/void', [AdminPortalLicenseController::class, 'voidInvoice'])->name('invoices.void');
        Route::get('/webhooks', fn () => view('admin.webhooks'))->name('webhooks');
        Route::get('/webhooks/deliveries/{id}', [AdminPortalController::class, 'webhookDelivery'])->name('webhook-delivery');
        Route::get('/metrics', fn () => view('admin.metrics'))->name('metrics');
        Route::get('/trials', fn () => view('admin.trials'))->name('trials');
        Route::get('/platform-config', fn () => view('admin.platform-config'))->name('platform-config');
    });
});
