<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function store(string $orderId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'entitlement_id' => ['sometimes', 'uuid'],
            'refund_type' => ['required', 'in:full,partial'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:8'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:64'],
            'auto_revoke' => ['sometimes', 'boolean'],
            'initiated_by' => ['sometimes', 'nullable', 'string', 'max:32'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $refund = Refund::query()->create([
            'order_id' => $orderId,
            'entitlement_id' => $data['entitlement_id'] ?? null,
            'refund_type' => $data['refund_type'],
            'amount_cents' => $data['amount_cents'],
            'currency' => $data['currency'] ?? 'USD',
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'auto_revoke' => $data['auto_revoke'] ?? true,
            'initiated_by' => $data['initiated_by'] ?? 'admin',
            'notes' => $data['notes'] ?? null,
        ]);

        return ApiResponse::success([
            'refund' => $refund->toArray(),
        ], 201);
    }
}
