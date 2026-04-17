<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\LicenseKey;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function expiring(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'product_id' => ['sometimes', 'nullable', 'uuid'],
            'status' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);

        $days = (int) ($payload['days'] ?? 30);
        $cutoff = now()->addDays($days);

        $query = LicenseKey::query()
            ->with('entitlement.plan.product')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), $cutoff]);

        if (! empty($payload['status'])) {
            $query->where('status', $payload['status']);
        }

        if (! empty($payload['product_id'])) {
            $query->whereHas('entitlement.plan', static fn ($q) => $q->where('product_id', $payload['product_id']));
        }

        $licenses = $query->orderBy('expires_at')->get()->map(static fn (LicenseKey $license): array => [
            'id' => $license->id,
            'key_display' => $license->key_display,
            'status' => $license->status,
            'expires_at' => $license->expires_at?->toISOString(),
            'product_code' => $license->entitlement?->plan?->product?->code,
            'plan_code' => $license->entitlement?->plan?->code,
        ])->all();

        return ApiResponse::success([
            'report' => [
                'days' => $days,
                'count' => count($licenses),
                'licenses' => $licenses,
            ],
        ]);
    }

    public function activations(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['sometimes', 'nullable', 'uuid'],
            'status' => ['sometimes', 'nullable', 'string', 'max:32'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $query = Activation::query()->with('license.entitlement.plan.product');

        if (! empty($payload['status'])) {
            $query->where('status', $payload['status']);
        }

        if (! empty($payload['from'])) {
            $query->whereDate('activated_at', '>=', $payload['from']);
        }

        if (! empty($payload['to'])) {
            $query->whereDate('activated_at', '<=', $payload['to']);
        }

        if (! empty($payload['product_id'])) {
            $query->whereHas('license.entitlement.plan', static fn ($q) => $q->where('product_id', $payload['product_id']));
        }

        $activations = $query->orderByDesc('activated_at')->get()->map(static fn (Activation $activation): array => [
            'id' => $activation->id,
            'activation_code' => $activation->activation_code,
            'status' => $activation->status,
            'domain' => $activation->domain,
            'product_code' => $activation->license?->entitlement?->plan?->product?->code,
            'activated_at' => $activation->activated_at?->toISOString(),
            'last_validated_at' => $activation->last_validated_at?->toISOString(),
        ])->all();

        return ApiResponse::success([
            'report' => [
                'count' => count($activations),
                'activations' => $activations,
            ],
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'string', 'in:expiring,activations'],
            'format' => ['sometimes', 'string', 'in:json,csv'],
            'product_id' => ['sometimes', 'nullable', 'uuid'],
            'status' => ['sometimes', 'nullable', 'string', 'max:32'],
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $format = $payload['format'] ?? 'csv';

        if ($format === 'json') {
            return ApiResponse::success([
                'export' => $payload,
            ]);
        }

        return ApiResponse::success([
            'export' => [
                'format' => 'csv',
                'download_url' => null,
                'ready' => true,
            ],
        ]);
    }
}
