<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'plans' => Plan::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'code' => ['required', 'string', 'max:64', 'unique:plans,code'],
            'name' => ['required', 'string', 'max:255'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual', 'lifetime', 'trial'])],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'max_activations' => ['nullable', 'integer', 'min:1'],
            'max_sites' => ['nullable', 'integer', 'min:1'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
        ]);

        $plan = Plan::query()->create([
            ...$payload,
            'currency' => $payload['currency'] ?? 'USD',
            'trial_days' => $payload['trial_days'] ?? 0,
        ]);

        return ApiResponse::success(['plan' => $plan->toArray()], 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::success([
            'plan' => Plan::query()->findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $plan = Plan::query()->findOrFail($id);

        $payload = $request->validate([
            'product_id' => ['sometimes', 'uuid', 'exists:products,id'],
            'code' => ['sometimes', 'string', 'max:64', Rule::unique('plans', 'code')->ignore($plan->getKey())],
            'name' => ['sometimes', 'string', 'max:255'],
            'billing_cycle' => ['sometimes', Rule::in(['monthly', 'annual', 'lifetime', 'trial'])],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:8'],
            'max_activations' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_sites' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'trial_days' => ['sometimes', 'integer', 'min:0'],
        ]);

        $plan->fill($payload)->save();

        return ApiResponse::success(['plan' => $plan->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $plan = Plan::query()->findOrFail($id);
        $plan->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
