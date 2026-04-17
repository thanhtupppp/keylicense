<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntitlementController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(['entitlements' => Entitlement::query()->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'plan_id' => ['required', 'uuid'],
            'status' => ['sometimes', 'string', 'max:32'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'auto_renew' => ['sometimes', 'boolean'],
            'max_activations' => ['sometimes', 'nullable', 'integer'],
            'max_sites' => ['sometimes', 'nullable', 'integer'],
        ]);

        return ApiResponse::success(['entitlement' => Entitlement::query()->create($payload)]);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::success(['entitlement' => Entitlement::query()->findOrFail($id)]);
    }
}
