<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Subscription;
use App\Services\Billing\CouponService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function validate(Request $request, CouponService $couponService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:128'],
        ]);

        return ApiResponse::success($couponService->validate($payload['code']));
    }

    public function apply(Request $request, CouponService $couponService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:128'],
            'subscription_id' => ['required', 'uuid'],
        ]);

        $subscription = Subscription::query()->findOrFail($payload['subscription_id']);

        return ApiResponse::success($couponService->apply($payload['code'], $subscription));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'string', 'max:32'],
            'discount_value' => ['required', 'integer'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'max_redemptions' => ['sometimes', 'nullable', 'integer'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $coupon = Coupon::query()->create($payload);

        return ApiResponse::success(['coupon' => $coupon]);
    }
}
