<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanPricing;
use App\Services\Billing\PricingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanPricingController extends Controller
{
    public function index(string $planId, PricingService $service): JsonResponse
    {
        $plan = Plan::query()->findOrFail($planId);

        $pricingRecords = PlanPricing::query()
            ->where('plan_id', $plan->id)
            ->orderByDesc('is_default')
            ->orderBy('currency')
            ->get();

        $rows = $pricingRecords
            ->map(static fn (PlanPricing $row): array => [
                'currency' => $row->currency,
                'price_cents' => $row->price_cents,
                'is_default' => $row->is_default,
                'valid_from' => $row->valid_from?->toISOString(),
                'valid_until' => $row->valid_until?->toISOString(),
            ])
            ->values()
            ->all();

        $resolved = $service->resolvePrice($plan, collect($rows)->firstWhere('is_default', true)['currency'] ?? null);

        return ApiResponse::success([
            'plan_id' => $plan->id,
            'resolved' => $resolved,
            'pricing' => $rows,
        ]);
    }

    public function store(string $planId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'currency' => ['required', 'string', 'max:8'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
            'valid_from' => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $plan = Plan::query()->findOrFail($planId);

        $pricing = PlanPricing::query()->updateOrCreate(
            [
                'plan_id' => $plan->id,
                'currency' => strtoupper($data['currency']),
            ],
            [
                'price_cents' => $data['price_cents'],
                'is_default' => $data['is_default'] ?? false,
                'valid_from' => $data['valid_from'] ?? now(),
                'valid_until' => $data['valid_until'] ?? null,
            ]
        );

        return ApiResponse::success([
            'pricing' => [
                'currency' => $pricing->currency,
                'price_cents' => $pricing->price_cents,
                'is_default' => $pricing->is_default,
                'valid_from' => $pricing->valid_from?->toISOString(),
                'valid_until' => $pricing->valid_until?->toISOString(),
            ],
        ]);
    }

    public function update(string $planId, string $currency, Request $request): JsonResponse
    {
        $data = $request->validate([
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
            'valid_from' => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $plan = Plan::query()->findOrFail($planId);

        $pricing = PlanPricing::query()
            ->where('plan_id', $plan->id)
            ->where('currency', strtoupper($currency))
            ->firstOrFail();

        $pricing->update($data);

        return ApiResponse::success([
            'pricing' => [
                'currency' => $pricing->currency,
                'price_cents' => $pricing->price_cents,
                'is_default' => $pricing->is_default,
                'valid_from' => $pricing->valid_from?->toISOString(),
                'valid_until' => $pricing->valid_until?->toISOString(),
            ],
        ]);
    }
}
