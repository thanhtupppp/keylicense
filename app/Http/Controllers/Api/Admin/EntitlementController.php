<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntitlementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'customer_id' => ['nullable', 'uuid'],
            'org_id' => ['nullable', 'uuid'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'auto_renew' => ['nullable', 'boolean'],
            'max_activations' => ['nullable', 'integer', 'min:1'],
            'max_sites' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($payload['customer_id']) && empty($payload['org_id'])) {
            return ApiResponse::error('VALIDATION_ERROR', 'customer_id or org_id is required.', 422);
        }

        $entitlement = Entitlement::query()->create([
            ...$payload,
            'status' => 'active',
            'auto_renew' => $payload['auto_renew'] ?? false,
        ]);

        return ApiResponse::success(['entitlement' => $entitlement->toArray()], 201);
    }
}
