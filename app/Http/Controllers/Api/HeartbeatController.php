<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SeatNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Product;
use App\Services\HeartbeatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HeartbeatController extends Controller
{
    public function __construct(
        private HeartbeatService $heartbeatService
    ) {}

    /**
     * Send heartbeat for a floating license.
     *
     * POST /api/v1/licenses/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'license_key' => ['required', 'string', 'regex:/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/'],
            'device_fingerprint' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dữ liệu đầu vào không hợp lệ',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        $validated = $validator->validated();

        // Get product from request attributes
        /** @var Product $product */
        $product = $request->attributes->get('product');

        // Hash the license key
        $keyHash = hash('sha256', $validated['license_key']);
        $deviceFpHash = hash('sha256', $validated['device_fingerprint']);

        // Find license
        $license = License::where('key_hash', $keyHash)
            ->where('product_id', $product->id)
            ->first();

        if (!$license) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_NOT_FOUND',
                    'message' => 'License key not found',
                ],
            ], 404);
        }

        try {
            // Send heartbeat
            $this->heartbeatService->heartbeat($license, $deviceFpHash);

            return response()->json([
                'success' => true,
                'data' => [
                    'heartbeat_received' => true,
                    'timestamp' => now()->toIso8601String(),
                ],
                'error' => null,
            ], 200);
        } catch (SeatNotFoundException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'SEAT_NOT_FOUND',
                    'message' => 'No active seat found for this device',
                ],
            ], 404);
        }
    }
}
