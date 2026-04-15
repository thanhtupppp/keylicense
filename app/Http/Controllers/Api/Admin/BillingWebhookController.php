<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DunningLog;
use App\Models\LicenseKey;
use App\Models\Subscription;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingWebhookController extends Controller
{
    public function paymentFailed(Request $request): JsonResponse
    {
        $subscription = Subscription::query()->findOrFail($request->input('subscription_id'));
        $subscription->update(['status' => 'past_due']);

        DunningLog::query()->create([
            'subscription_id' => $subscription->id,
            'step' => 1,
            'action' => 'payment_failed',
            'executed_at' => now(),
            'result' => 'queued',
            'notes' => null,
        ]);

        return ApiResponse::success([
            'processed' => true,
            'status' => 'past_due',
        ]);
    }

    public function paymentSucceeded(Request $request): JsonResponse
    {
        $subscription = Subscription::query()->findOrFail($request->input('subscription_id'));
        $subscription->update([
            'status' => 'active',
            'cancel_at_period_end' => false,
        ]);

        LicenseKey::query()
            ->where('entitlement_id', $subscription->entitlement_id)
            ->update(['status' => 'active']);

        DunningLog::query()->create([
            'subscription_id' => $subscription->id,
            'step' => 1,
            'action' => 'payment_recovered',
            'executed_at' => now(),
            'result' => 'recovered',
            'notes' => null,
        ]);

        return ApiResponse::success([
            'processed' => true,
            'status' => 'active',
        ]);
    }
}
