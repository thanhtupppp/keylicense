<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Billing\RefundService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChargebackController extends Controller
{
    public function store(Request $request, RefundService $refundService): JsonResponse
    {
        $payload = $request->validate([
            'subscription_id' => ['required', 'uuid'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $subscription = Subscription::query()->findOrFail($payload['subscription_id']);
        $refundService->revokeForSubscription($subscription, $payload['reason'] ?? 'chargeback');

        return ApiResponse::success([
            'processed' => true,
            'subscription_id' => $subscription->id,
            'status' => 'cancelled',
            'reason' => $payload['reason'] ?? 'chargeback',
            'reversal_type' => 'chargeback',
        ]);
    }
}
