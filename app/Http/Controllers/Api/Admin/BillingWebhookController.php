<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DunningLog;
use App\Models\LicenseKey;
use App\Models\Subscription;
use App\Services\Billing\DunningOrchestrator;
use App\Services\Billing\RenewalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingWebhookController extends Controller
{
    public function __construct(private readonly DunningOrchestrator $orchestrator)
    {
    }

    public function paymentFailed(Request $request): JsonResponse
    {
        $subscription = Subscription::query()->findOrFail($request->input('subscription_id'));
        $this->orchestrator->handlePaymentFailed($subscription);

        DunningLog::query()->create([
            'subscription_id' => $subscription->id,
            'step' => 0,
            'action' => 'payment_failed',
            'executed_at' => now(),
            'result' => 'queued',
            'notes' => null,
        ]);

        return ApiResponse::success([
            'processed' => true,
            'status' => 'past_due',
            'subscription_id' => $subscription->id,
        ]);
    }

    public function paymentSucceeded(Request $request, RenewalService $renewalService): JsonResponse
    {
        $subscription = Subscription::query()->findOrFail($request->input('subscription_id'));
        $renewed = $renewalService->renew($subscription);
        $this->orchestrator->handlePaymentSucceeded($renewed);

        LicenseKey::query()
            ->where('entitlement_id', $subscription->entitlement_id)
            ->update(['status' => 'active']);

        DunningLog::query()->create([
            'subscription_id' => $subscription->id,
            'step' => 0,
            'action' => 'payment_recovered',
            'executed_at' => now(),
            'result' => 'recovered',
            'notes' => null,
        ]);

        return ApiResponse::success([
            'processed' => true,
            'status' => 'active',
            'subscription_id' => $subscription->id,
            'current_period_end' => $renewed->current_period_end?->toISOString(),
        ]);
    }

    public function orderCreated(Request $request, RenewalService $renewalService): JsonResponse
    {
        $payload = $request->validate([
            'subscription_id' => ['required', 'uuid'],
        ]);

        $subscription = Subscription::query()->findOrFail($payload['subscription_id']);
        $renewed = $renewalService->renew($subscription);

        return ApiResponse::success([
            'processed' => true,
            'status' => 'active',
            'subscription_id' => $subscription->id,
            'current_period_end' => $renewed->current_period_end?->toISOString(),
        ]);
    }
}
