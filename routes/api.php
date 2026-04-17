<?php

use App\Http\Controllers\Api\Admin\AdminSessionController;
use App\Http\Controllers\Api\Admin\ApiKeyController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\BillingWebhookController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DunningController;
use App\Http\Controllers\Api\Admin\EntitlementController;
use App\Http\Controllers\Api\Admin\InvoiceController;
use App\Http\Controllers\Api\Admin\JobDlqController;
use App\Http\Controllers\Api\Admin\LicenseController;
use App\Http\Controllers\Api\Admin\MfaController;
use App\Http\Controllers\Api\Admin\FeatureController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Admin\PlanFeatureController;
use App\Http\Controllers\Api\Admin\PlanPricingController;
use App\Http\Controllers\Api\Admin\PlatformConfigController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\RefundController;
use App\Http\Controllers\Api\Admin\ResellerController;
use App\Http\Controllers\Api\Admin\ProductVersionController;
use App\Http\Controllers\Api\Admin\RestrictionController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\UsageController;
use App\Http\Controllers\Api\Admin\WebhookDeliveryController;
use App\Http\Controllers\Api\Client\ClientEnvironmentController;
use App\Http\Controllers\Api\Client\LicenseClientController;
use App\Http\Controllers\Api\Client\UpdateCheckController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Customer\CustomerVerificationController;
use App\Http\Controllers\Api\Customer\DataRequestController;
use App\Http\Controllers\Api\Customer\NotificationPreferenceController;
use App\Http\Controllers\Api\Customer\OnboardingController;
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
        Route::middleware('admin.role:super_admin,admin')->group(function (): void {
            Route::get('/dashboard', [DashboardController::class, 'show']);
            Route::get('/products', [ProductController::class, 'index']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::get('/products/{id}', [ProductController::class, 'show']);
            Route::patch('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);
            Route::get('/plans', [PlanController::class, 'index']);
            Route::post('/plans', [PlanController::class, 'store']);
            Route::get('/plans/{id}', [PlanController::class, 'show']);
            Route::patch('/plans/{id}', [PlanController::class, 'update']);
            Route::delete('/plans/{id}', [PlanController::class, 'destroy']);
            Route::get('/entitlements', [EntitlementController::class, 'index']);
            Route::post('/entitlements', [EntitlementController::class, 'store']);
            Route::get('/entitlements/{id}', [EntitlementController::class, 'show']);
            Route::get('/products/{productId}/versions', [ProductVersionController::class, 'index']);
            Route::post('/products/{productId}/versions', [ProductVersionController::class, 'store']);
            Route::post('/licenses/issue', [LicenseController::class, 'issue']);
            Route::get('/licenses/{id}/history', [LicenseController::class, 'history']);
            Route::post('/licenses/{id}/revoke', [LicenseController::class, 'revoke']);
            Route::post('/licenses/{id}/suspend', [LicenseController::class, 'suspend']);
            Route::post('/licenses/{id}/unsuspend', [LicenseController::class, 'unsuspend']);
            Route::post('/licenses/{id}/extend', [LicenseController::class, 'extend']);
            Route::get('/reports/expiring', [ReportController::class, 'expiring']);
            Route::get('/reports/activations', [ReportController::class, 'activations']);
            Route::get('/reports/export', [ReportController::class, 'export']);
            Route::get('/features', [FeatureController::class, 'index']);
            Route::post('/features', [FeatureController::class, 'store']);
            Route::patch('/features/{id}', [FeatureController::class, 'update']);
            Route::delete('/features/{id}', [FeatureController::class, 'destroy']);
            Route::get('/plans/{planId}/features', [PlanFeatureController::class, 'index']);
            Route::post('/plans/{planId}/features', [PlanFeatureController::class, 'store']);
            Route::delete('/plans/{planId}/features/{featureId}', [PlanFeatureController::class, 'destroy']);
            Route::post('/api-keys', [ApiKeyController::class, 'issue']);
            Route::post('/api-keys/{id}/rotate', [ApiKeyController::class, 'rotate']);
            Route::delete('/api-keys/{id}', [ApiKeyController::class, 'revoke']);
            Route::post('/orders/{orderId}/refund', [RefundController::class, 'store']);
            Route::post('/resellers', [ResellerController::class, 'store']);
        });

        Route::middleware('admin.role:super_admin,admin,support')->group(function (): void {
            Route::get('/plans/{planId}/pricing', [PlanPricingController::class, 'index']);
            Route::post('/plans/{planId}/pricing', [PlanPricingController::class, 'store']);
            Route::patch('/plans/{planId}/pricing/{currency}', [PlanPricingController::class, 'update']);
            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
            Route::post('/invoices/{id}/void', [InvoiceController::class, 'void']);
            Route::post('/orders/{orderId}/invoice', [InvoiceController::class, 'createFromOrder']);
            Route::get('/usage/summaries', [UsageController::class, 'index']);
            Route::get('/resellers', [ResellerController::class, 'index']);
            Route::get('/resellers/{resellerId}/pools', [ResellerController::class, 'pools']);
            Route::get('/resellers/{resellerId}/pools/{poolId}/keys', [ResellerController::class, 'poolKeys']);
            Route::get('/resellers/{resellerId}/reports', [ResellerController::class, 'reports']);
        });

        Route::middleware('admin.role:super_admin')->group(function (): void {
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
            Route::get('/licenses/{licenseId}/ip-allowlist', [RestrictionController::class, 'allowlistIndex']);
            Route::post('/licenses/{licenseId}/ip-allowlist', [RestrictionController::class, 'allowlistStore']);
            Route::delete('/licenses/{licenseId}/ip-allowlist/{entryId}', [RestrictionController::class, 'allowlistDestroy']);
            Route::get('/ip-blocklist', [RestrictionController::class, 'blocklistIndex']);
            Route::post('/ip-blocklist', [RestrictionController::class, 'blocklistStore']);
            Route::delete('/ip-blocklist/{entryId}', [RestrictionController::class, 'blocklistDestroy']);
            Route::get('/plans/{planId}/geo-restrictions', [RestrictionController::class, 'geoIndex']);
            Route::put('/plans/{planId}/geo-restrictions', [RestrictionController::class, 'geoStore']);
            Route::delete('/plans/{planId}/geo-restrictions/{restrictionId}', [RestrictionController::class, 'geoDestroy']);
            Route::post('/webhooks/deliver-test', [WebhookDeliveryController::class, 'index']);
            Route::get('/users/{id}/sessions', [AdminSessionController::class, 'listUserSessions']);
            Route::delete('/users/{id}/sessions', [AdminSessionController::class, 'forceLogout']);
            Route::post('/users/{id}/unlock', [AdminSessionController::class, 'unlock']);
        });

        Route::middleware('admin.role:super_admin,admin,finance')->group(function (): void {
            Route::get('/usage/records', [UsageController::class, 'index']);
        });

        Route::middleware('admin.role:super_admin,admin,reseller_manager')->group(function (): void {
            Route::post('/resellers/auth/login', [ResellerController::class, 'auth']);
            Route::post('/resellers/{resellerId}/pools', [ResellerController::class, 'createPool']);
            Route::post('/resellers/{resellerId}/pools/{poolId}/assign', [ResellerController::class, 'assignPool']);
        });

        Route::get('/auth/sessions', [AdminSessionController::class, 'index']);
        Route::delete('/auth/sessions/{id}', [AdminSessionController::class, 'destroy']);
        Route::delete('/auth/sessions', [AdminSessionController::class, 'destroyOthers']);
        Route::delete('/auth/sessions/revoke-others', [AdminSessionController::class, 'destroyOthers']);
        Route::get('/auth/login-history', [AdminSessionController::class, 'loginHistory']);
    });

    Route::middleware('client.api-key')->prefix('client')->group(function (): void {
        Route::post('/licenses/activate', [LicenseClientController::class, 'activate']);
        Route::post('/licenses/validate', [LicenseClientController::class, 'validateLicense']);
        Route::post('/licenses/deactivate', [LicenseClientController::class, 'deactivate']);
        Route::post('/licenses/offline/request', [LicenseClientController::class, 'requestOfflineChallenge']);
        Route::post('/licenses/offline/confirm', [LicenseClientController::class, 'confirmOfflineChallenge']);
        Route::post('/updates/check', [UpdateCheckController::class, 'show']);
        Route::get('/environment', [ClientEnvironmentController::class, 'show']);
    });

    Route::post('/customer/data-requests', [DataRequestController::class, 'store']);
    Route::get('/customer/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::patch('/customer/notification-preferences', [NotificationPreferenceController::class, 'update']);
    Route::post('/customer/notification-preferences/unsubscribe', [NotificationPreferenceController::class, 'unsubscribe']);
    Route::get('/customer/verification', [CustomerVerificationController::class, 'show']);
    Route::post('/customer/verification/verify', [CustomerVerificationController::class, 'verify']);
    Route::post('/customer/verification/resend', [CustomerVerificationController::class, 'resend']);
    Route::get('/customer/onboarding', [OnboardingController::class, 'show']);
    Route::post('/customer/onboarding/skip', [OnboardingController::class, 'skip']);
});
