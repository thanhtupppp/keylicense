<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'plans' => Plan::query()->get()->map(static fn (Plan $plan): array => [
                'id' => $plan->id,
                'product_id' => $plan->product_id,
                'code' => $plan->code,
                'name' => $plan->name,
                'billing_cycle' => $plan->billing_cycle,
            ])->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'billing_cycle' => ['required', 'string', 'max:32'],
            'price_cents' => ['sometimes', 'nullable', 'integer'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'max_activations' => ['sometimes', 'nullable', 'integer'],
            'max_sites' => ['sometimes', 'nullable', 'integer'],
            'trial_days' => ['sometimes', 'nullable', 'integer'],
        ]);

        $plan = Plan::query()->create($payload);

        return ApiResponse::success([
            'plan' => $plan,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::success(['plan' => Plan::query()->findOrFail($id)]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $plan = Plan::query()->findOrFail($id);
        $plan->fill($request->validate([
            'product_id' => ['sometimes', 'uuid'],
            'code' => ['sometimes', 'string', 'max:128'],
            'name' => ['sometimes', 'string', 'max:255'],
            'billing_cycle' => ['sometimes', 'string', 'max:32'],
            'price_cents' => ['sometimes', 'nullable', 'integer'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'max_activations' => ['sometimes', 'nullable', 'integer'],
            'max_sites' => ['sometimes', 'nullable', 'integer'],
            'trial_days' => ['sometimes', 'nullable', 'integer'],
        ]))->save();

        return ApiResponse::success(['plan' => $plan->refresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        Plan::query()->findOrFail($id)->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
