<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunBulkOperationJob;
use App\Models\LicenseKey;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function issue(Request $request): JsonResponse
    {
        return ApiResponse::success(['issued' => true], 201);
    }

    public function history(string $id): JsonResponse
    {
        return ApiResponse::success(['license_id' => $id, 'events' => []]);
    }

    public function revoke(string $id): JsonResponse
    {
        $license = LicenseKey::query()->findOrFail($id);
        $license->update(['status' => 'revoked']);

        return ApiResponse::success(['license_id' => $id, 'status' => 'revoked']);
    }

    public function suspend(string $id): JsonResponse
    {
        $license = LicenseKey::query()->findOrFail($id);
        $license->update(['status' => 'suspended']);

        return ApiResponse::success(['license_id' => $id, 'status' => 'suspended']);
    }

    public function unsuspend(string $id): JsonResponse
    {
        $license = LicenseKey::query()->findOrFail($id);
        $license->update(['status' => 'active']);

        return ApiResponse::success(['license_id' => $id, 'status' => 'active']);
    }

    public function extend(string $id): JsonResponse
    {
        $license = LicenseKey::query()->findOrFail($id);
        $license->update(['expires_at' => now()->addMonth()]);

        return ApiResponse::success(['license_id' => $id, 'extended' => true]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'operation' => ['required', 'string', 'max:64'],
            'license_ids' => ['required', 'array', 'min:1'],
            'license_ids.*' => ['uuid'],
        ]);

        RunBulkOperationJob::dispatch($data['operation'], $data['license_ids']);

        return ApiResponse::success([
            'queued' => true,
            'operation' => $data['operation'],
            'count' => count($data['license_ids']),
        ], 202);
    }
}
