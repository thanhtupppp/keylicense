<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $customerId = $request->header('X-Customer-Id') ?? $request->input('customer_id');

        $customer = Customer::query()->findOrFail($customerId);
        $onboarding = data_get($customer->metadata, 'onboarding', [
            'step' => 'verify_email',
            'completed' => false,
        ]);

        return ApiResponse::success([
            'customer_id' => $customer->getKey(),
            'onboarding' => $onboarding,
        ]);
    }

    public function skip(Request $request): JsonResponse
    {
        $customerId = $request->header('X-Customer-Id') ?? $request->input('customer_id');
        $customer = Customer::query()->findOrFail($customerId);
        $metadata = $customer->metadata ?? [];
        $metadata['onboarding'] = [
            'step' => 'complete',
            'completed' => true,
        ];

        $customer->update(['metadata' => $metadata]);

        return ApiResponse::success(['skipped' => true]);
    }
}
