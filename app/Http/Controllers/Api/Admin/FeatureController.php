<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeatureController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'features' => Feature::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:features,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $feature = Feature::query()->create([
            ...$payload,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return ApiResponse::success(['feature' => $feature], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $feature = Feature::query()->findOrFail($id);

        $payload = $request->validate([
            'code' => ['sometimes', 'string', 'max:64', Rule::unique('features', 'code')->ignore($feature->getKey())],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $feature->fill($payload)->save();

        return ApiResponse::success(['feature' => $feature->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $feature = Feature::query()->findOrFail($id);
        $feature->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
