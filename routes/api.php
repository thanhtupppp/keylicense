<?php

use App\Http\Controllers\Api\Admin\AdminSessionController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\DunningController;
use App\Http\Controllers\Api\Admin\EntitlementController;
use App\Http\Controllers\Api\Admin\LicenseController;
use App\Http\Controllers\Api\Admin\ApiKeyController;
use App\Http\Controllers\Api\Admin\BillingWebhookController;
use App\Http\Controllers\Api\Admin\InvoiceController;
use App\Http\Controllers\Api\Admin\JobDlqController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Admin\WebhookDeliveryController;
use App\Http\Controllers\Api\Admin\PlanPricingController;
use App\Http\Controllers\Api\Admin\PlatformConfigController;
use App\Http\Controllers\Api\Admin\RestrictionController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\UsageController;
use App\Http\Controllers\Api\Admin\RefundController;
use App\Http\Controllers\Api\Admin\MfaController;
use App\Http\Controllers\Api\Admin\ResellerController;
use App\Http\Controllers\Api\Client\ClientEnvironmentController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Customer\DataRequestController;
use App\Http\Controllers\Api\Customer\NotificationPreferenceController;
use App\Http\Controllers\Api\Customer\OnboardingController;
use App\Http\Controllers\Api\Client\LicenseClientController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', [HealthController::class, 'health']);
    Route::get('/status', [HealthController::class, 'status']);
    Route::get('/version', [HealthController::class, 'version']);

    Route::post('/admin/auth/login', [AuthController::class, 'login']);
    Route::post('/admin/auth/mfa/setup', [MfaController::class, 'setup']);
    Route::post('/admin/auth/mfa/verify-setup', [MfaController::class, 'verifySetup']);
    Route::post('/admin/auth/mfa/challenge', [MfaController::class, 'challenge']);
    Route::post('/admin/auth/mfa/disable', [MfaController::class, 'disable']);
    Route::post('/admin/auth/mfa/regenerate-backup-codes', [MfaController::class, 'regenerateBackupCodes']);
    Route::post('/customer/auth/register', [CustomerAuthController::class, 'register']);
    Route::post('/customer/auth/verify-email', [CustomerAuthController::class, 'verifyEmail']);
    Route::post('/customer/auth/resend-verification', [CustomerAuthController::class, 'resendVerification']);

    Route::middleware('admin.auth')->prefix('admin')->group(function (): void {
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/plans', [PlanController::class, 'store']);
        Route::get('/plans/{planId}/pricing', [PlanPricingController::class, 'index']);
        Route::post('/plans/{planId}/pricing', [PlanPricingController::class, 'store']);
        Route::patch('/plans/{planId}/pricing/{currency}', [PlanPricingController::class, 'update']);
        Route::post('/entitlements', [EntitlementController::class, 'store']);
        Route::post('/licenses/issue', [LicenseController::class, 'issue']);
        Route::post('/api-keys', [ApiKeyController::class, 'issue']);
        Route::post('/api-keys/{id}/rotate', [ApiKeyController::class, 'rotate']);
        Route::delete('/api-keys/{id}', [ApiKeyController::class, 'revoke']);
        Route::post('/orders/{orderId}/refund', [RefundController::class, 'store']);
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
        Route::post('/invoices/{id}/void', [InvoiceController::class, 'void']);
        Route::post('/orders/{orderId}/invoice', [InvoiceController::class, 'createFromOrder']);

        Route::get('/platform/config', [PlatformConfigController::class, 'index']);
        Route::patch('/platform/config', [PlatformConfigController::class, 'update']);
        Route::get('/platform/config/{key}', [PlatformConfigController::class, 'show']);

        Route::get('/dunning/configs', [DunningController::class, 'configs']);
        Route::put('/dunning/configs', [DunningController::class, 'updateConfigs']);
        Route::get('/dunning/logs', [DunningController::class, 'logs']);
        Route::get('/reports/dunning', [DunningController::class, 'report']);
        Route::post('/subscriptions/{id}/retry-payment', [DunningController::class, 'retryPayment']);
        Route::post('/billing-webhooks/payment-failed', [BillingWebhookController::class, 'paymentFailed']);
        Route::post('/billing-webhooks/payment-succeeded', [BillingWebhookController::class, 'paymentSucceeded']);
        Route::get('/jobs/dlq', [JobDlqController::class, 'index']);
        Route::post('/jobs/dlq/{id}/retry', [JobDlqController::class, 'retry']);
        Route::post('/jobs/dlq/{id}/discard', [JobDlqController::class, 'discard']);
        Route::get('/webhook-deliveries', [WebhookDeliveryController::class, 'index']);

        Route::get('/usage/summaries', [UsageController::class, 'index']);
        Route::post('/usage/records', [UsageController::class, 'store']);
        Route::get('/plans/{planId}/usage-limits', [UsageController::class, 'limits']);

        Route::get('/resellers', [ResellerController::class, 'index']);
        Route::post('/resellers', [ResellerController::class, 'store']);
        Route::post('/resellers/auth/login', [ResellerController::class, 'auth']);
        Route::get('/resellers/{resellerId}/pools', [ResellerController::class, 'pools']);
        Route::post('/resellers/{resellerId}/pools', [ResellerController::class, 'createPool']);
        Route::get('/resellers/{resellerId}/pools/{poolId}/keys', [ResellerController::class, 'poolKeys']);
        Route::post('/resellers/{resellerId}/pools/{poolId}/assign', [ResellerController::class, 'assignPool']);
        Route::get('/resellers/{resellerId}/reports', [ResellerController::class, 'reports']);
        Route::get('/licenses/{licenseId}/ip-allowlist', [RestrictionController::class, 'allowlistIndex']);
        Route::post('/licenses/{licenseId}/ip-allowlist', [RestrictionController::class, 'allowlistStore']);
        Route::delete('/licenses/{licenseId}/ip-allowlist/{entryId}', [RestrictionController::class, 'allowlistDestroy']);
        Route::get('/ip-blocklist', [RestrictionController::class, 'blocklistIndex']);
        Route::post('/ip-blocklist', [RestrictionController::class, 'blocklistStore']);
        Route::delete('/ip-blocklist/{entryId}', [RestrictionController::class, 'blocklistDestroy']);
        Route::get('/plans/{planId}/geo-restrictions', [RestrictionController::class, 'geoIndex']);
        Route::put('/plans/{planId}/geo-restrictions', [RestrictionController::class, 'geoStore']);
        Route::delete('/plans/{planId}/geo-restrictions/{restrictionId}', [RestrictionController::class, 'geoDestroy']);

        Route::get('/licenses/{licenseId}/ip-allowlist', [RestrictionController::class, 'licenseAllowlist']);
        Route::post('/licenses/{licenseId}/ip-allowlist', [RestrictionController::class, 'storeLicenseAllowlist']);
        Route::delete('/licenses/{licenseId}/ip-allowlist/{entryId}', [RestrictionController::class, 'deleteLicenseAllowlist']);
        Route::get('/ip-blocklist', [RestrictionController::class, 'blocklist']);
        Route::post('/ip-blocklist', [RestrictionController::class, 'storeBlocklist']);
        Route::delete('/ip-blocklist/{id}', [RestrictionController::class, 'deleteBlocklist']);
        Route::get('/plans/{planId}/geo-restrictions', [RestrictionController::class, 'planGeoRestrictions']);
        Route::put('/plans/{planId}/geo-restrictions', [RestrictionController::class, 'upsertPlanGeoRestrictions']);

        Route::post('/webhooks/deliver-test', [WebhookDeliveryController::class, 'index']);

        Route::get('/auth/sessions', [AdminSessionController::class, 'index']);
        Route::delete('/auth/sessions/{id}', [AdminSessionController::class, 'destroy']);
        Route::delete('/auth/sessions', [AdminSessionController::class, 'destroyOthers']);
        Route::delete('/auth/sessions/revoke-others', [AdminSessionController::class, 'destroyOthers']);
        Route::get('/auth/login-history', [AdminSessionController::class, 'loginHistory']);

        Route::get('/users/{id}/sessions', [AdminSessionController::class, 'listUserSessions']);
        Route::delete('/users/{id}/sessions', [AdminSessionController::class, 'forceLogout']);
        Route::post('/users/{id}/unlock', [AdminSessionController::class, 'unlock']);
    });

    Route::middleware('client.api-key')->prefix('client')->group(function (): void {
        Route::post('/licenses/activate', [LicenseClientController::class, 'activate']);
        Route::post('/licenses/validate', [LicenseClientController::class, 'validateLicense']);
        Route::get('/environment', [ClientEnvironmentController::class, 'show']);
    });

    Route::post('/customer/data-requests', [DataRequestController::class, 'store']);
    Route::get('/customer/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::patch('/customer/notification-preferences', [NotificationPreferenceController::class, 'update']);
    Route::post('/customer/notification-preferences/unsubscribe', [NotificationPreferenceController::class, 'unsubscribe']);
    Route::get('/customer/onboarding', [OnboardingController::class, 'show']);
    Route::post('/customer/onboarding/skip', [OnboardingController::class, 'skip']);
});
