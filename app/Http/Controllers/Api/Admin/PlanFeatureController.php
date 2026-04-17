<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanFeatureController extends Controller
{
    public function index(string $planId): JsonResponse
    {
        Plan::query()->findOrFail($planId);

        return ApiResponse::success([
            'plan_id' => $planId,
            'plan_features' => PlanFeature::query()->where('plan_id', $planId)->with('feature')->latest()->get(),
        ]);
    }

    public function store(Request $request, string $planId): JsonResponse
    {
        Plan::query()->findOrFail($planId);

        $payload = $request->validate([
            'feature_id' => ['required', 'uuid', 'exists:features,id'],
            'value_text' => ['nullable', 'string'],
            'value_json' => ['nullable', 'array'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $planFeature = PlanFeature::query()->updateOrCreate(
            ['plan_id' => $planId, 'feature_id' => $payload['feature_id']],
            [
                'value_text' => $payload['value_text'] ?? null,
                'value_json' => $payload['value_json'] ?? null,
                'is_enabled' => $payload['is_enabled'] ?? true,
            ]
        );

        return ApiResponse::success(['plan_feature' => $planFeature->fresh('feature')], 201);
    }

    public function destroy(string $planId, string $featureId): JsonResponse
    {
        $planFeature = PlanFeature::query()
            ->where('plan_id', $planId)
            ->where('feature_id', $featureId)
            ->firstOrFail();

        $planFeature->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
