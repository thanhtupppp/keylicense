<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DataRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DataRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'request_type' => ['required', 'in:erasure,portability,rectification'],
            'customer_id' => ['sometimes', 'uuid'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (! Schema::hasTable('data_requests')) {
            return ApiResponse::error('schema_not_ready', 'Data request storage is not available yet.', 503);
        }

        $requestRow = DataRequest::query()->create([
            'customer_id' => $data['customer_id'] ?? null,
            'request_type' => $data['request_type'],
            'status' => 'pending',
            'requested_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        if ($data['request_type'] === 'erasure' && ! empty($data['customer_id'])) {
            Customer::query()->whereKey($data['customer_id'])->update([
                'email' => 'deleted_'.$data['customer_id'].'@anonymized.invalid',
                'full_name' => '[DELETED]',
                'phone' => null,
                'metadata' => [],
            ]);
        }

        return ApiResponse::success([
            'request' => [
                'id' => $requestRow->id,
                'request_type' => $requestRow->request_type,
                'status' => $requestRow->status,
                'requested_at' => $requestRow->requested_at?->toISOString(),
            ],
        ], 201);
    }
}
