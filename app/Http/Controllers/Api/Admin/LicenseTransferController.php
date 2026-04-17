<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Services\Licensing\LicenseTransferService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseTransferController extends Controller
{
    public function store(Request $request, LicenseTransferService $transferService): JsonResponse
    {
        $payload = $request->validate([
            'license_key_id' => ['required', 'uuid'],
            'from_customer_id' => ['nullable', 'uuid'],
            'to_customer_id' => ['required', 'uuid'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $license = LicenseKey::query()->findOrFail($payload['license_key_id']);
        $transfer = $transferService->transfer(
            $license,
            $payload['from_customer_id'] ?? null,
            $payload['to_customer_id'],
            $payload['reason'] ?? 'transfer'
        );

        return ApiResponse::success([
            'transfer' => $transfer,
            'auto_revoke_activations' => true,
        ], 201);
    }
}
