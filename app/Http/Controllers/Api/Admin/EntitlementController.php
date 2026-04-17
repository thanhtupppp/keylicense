<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EntitlementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Entitlement::query()->with('plan.product');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->string('plan_id'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->string('customer_id'));
        }

        if ($request->filled('org_id')) {
            $query->where('org_id', $request->string('org_id'));
        }

        return ApiResponse::success([
            'entitlements' => $query->latest()->paginate((int) $request->integer('per_page', 15)),
        ]);
    }

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
            'status' => ['nullable', Rule::in(['active', 'trialing', 'expired', 'revoked', 'suspended'])],
        ]);

        if (empty($payload['customer_id']) && empty($payload['org_id'])) {
            return ApiResponse::error('VALIDATION_ERROR', 'customer_id or org_id is required.', 422);
        }

        $entitlement = Entitlement::query()->create([
            ...$payload,
            'status' => $payload['status'] ?? 'active',
            'auto_renew' => $payload['auto_renew'] ?? false,
        ]);

        return ApiResponse::success(['entitlement' => $entitlement->toArray()], 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::success([
            'entitlement' => Entitlement::query()->with('plan.product', 'licenses')->findOrFail($id),
        ]);
    }
}
