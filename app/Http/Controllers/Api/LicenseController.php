<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Product;
use App\Services\ActivationService;
use App\Services\OfflineTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    public function __construct(
        private ActivationService $activationService,
        private OfflineTokenService $offlineTokenService
    ) {}

    /**
     * Activate a license key.
     *
     * POST /api/v1/licenses/activate
     */
    public function activate(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'license_key' => ['required', 'string', 'regex:/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/'],
            'device_fingerprint' => 'nullable|string',
            'user_identifier' => 'nullable|string',
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

        if (empty($validated['device_fingerprint']) && empty($validated['user_identifier'])) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dữ liệu đầu vào không hợp lệ',
                    'details' => [
                        'device_fingerprint' => ['The device fingerprint field is required when user identifier is missing.'],
                    ],
                ],
            ], 422);
        }

        // Get product from request attributes (injected by auth:api_key middleware)
        /** @var Product $product */
        $product = $request->attributes->get('product');

        // Check if product is active
        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'PRODUCT_INACTIVE',
                    'message' => 'Product is inactive',
                ],
            ], 422);
        }

        // Hash the license key to find the license
        $keyHash = hash('sha256', $validated['license_key']);

        // Find license by key_hash
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

        // Check if license is soft deleted (treat as revoked)
        if ($license->trashed()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_REVOKED',
                    'message' => 'License key has been revoked',
                ],
            ], 422);
        }

        try {
            // Activate the license
            $activation = $this->activationService->activate(
                $license,
                $validated['device_fingerprint'],
                $validated['user_identifier'] ?? null,
                $request->ip()
            );

            // Issue offline token
            $offlineToken = $this->offlineTokenService->issue($activation, $product);

            return response()->json([
                'success' => true,
                'data' => [
                    'offline_token' => $offlineToken,
                    'activation_id' => $activation->id,
                    'activated_at' => $activation->activated_at->toIso8601String(),
                    'license_model' => $license->license_model,
                    'expiry_date' => $license->expiry_date?->toIso8601String(),
                ],
                'error' => null,
            ], 200);
        } catch (\Exception $e) {
            $errorCode = $e->getMessage();
            $errorMessages = [
                'LICENSE_REVOKED' => 'License key has been revoked',
                'LICENSE_SUSPENDED' => 'License key has been suspended',
                'LICENSE_EXPIRED' => 'License key has expired',
                'DEVICE_MISMATCH' => 'Device fingerprint does not match',
                'USER_MISMATCH' => 'User identifier does not match',
                'SEATS_EXHAUSTED' => 'All seats are currently in use',
            ];

            $message = $errorMessages[$errorCode] ?? 'Activation failed';

            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => $errorCode,
                    'message' => $message,
                ],
            ], 422);
        }
    }

    /**
     * Validate a license key online.
     *
     * POST /api/v1/licenses/validate
     */
    public function validate(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'license_key' => ['required', 'string', 'regex:/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/'],
            'device_fingerprint' => 'required|string',
            'offline_token' => 'nullable|string',
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

        // Check if license is soft deleted
        if ($license->trashed()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_REVOKED',
                    'message' => 'License key has been revoked',
                ],
            ], 422);
        }

        // Check license status
        if ($license->status->getValue() === 'revoked') {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_REVOKED',
                    'message' => 'License key has been revoked',
                ],
            ], 422);
        }

        if ($license->status->getValue() === 'suspended') {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_SUSPENDED',
                    'message' => 'License key has been suspended',
                ],
            ], 422);
        }

        if ($license->status->getValue() === 'expired') {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_EXPIRED',
                    'message' => 'License key has expired',
                ],
            ], 422);
        }

        // Check expiry date
        if ($license->expiry_date && $license->expiry_date->isPast()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_EXPIRED',
                    'message' => 'License key has expired',
                ],
            ], 422);
        }

        // Find activation
        $activation = null;
        if ($license->license_model === 'per-device') {
            $activation = $license->activations()
                ->where('device_fp_hash', $deviceFpHash)
                ->where('is_active', true)
                ->first();
        } elseif ($license->license_model === 'per-user') {
            $activation = $license->activations()
                ->where('is_active', true)
                ->first();
        } elseif ($license->license_model === 'floating') {
            $activation = $license->activations()
                ->where('device_fp_hash', $deviceFpHash)
                ->where('is_active', true)
                ->first();
        }

        if (!$activation) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'ACTIVATION_NOT_FOUND',
                    'message' => 'License not activated on this device',
                ],
            ], 422);
        }

        // Check device fingerprint for per-device licenses
        if ($license->license_model === 'per-device' && $activation->device_fp_hash !== $deviceFpHash) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'DEVICE_MISMATCH',
                    'message' => 'Device fingerprint does not match',
                ],
            ], 422);
        }

        // Check JTI revocation if offline_token is provided
        if (isset($validated['offline_token'])) {
            try {
                $claims = $this->offlineTokenService->verify($validated['offline_token'], $product);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'code' => 'INVALID_TOKEN',
                        'message' => $e->getMessage(),
                    ],
                ], 422);
            }
        }

        // Update last_verified_at
        DB::table('activations')
            ->where('id', $activation->id)
            ->update(['last_verified_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'license_status' => $license->status->getValue(),
                'license_model' => $license->license_model,
                'expiry_date' => $license->expiry_date?->toIso8601String(),
                'last_verified_at' => $activation->last_verified_at->toIso8601String(),
            ],
            'error' => null,
        ], 200);
    }

    /**
     * Deactivate a license key.
     *
     * POST /api/v1/licenses/deactivate
     */
    public function deactivate(Request $request): JsonResponse
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

        // Deactivate the license
        $deactivated = $this->activationService->deactivate(
            $license,
            $validated['device_fingerprint']
        );

        if (!$deactivated) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'ACTIVATION_NOT_FOUND',
                    'message' => 'No active activation found for this device',
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'deactivated' => true,
                'message' => 'License deactivated successfully',
            ],
            'error' => null,
        ], 200);
    }

    /**
     * Get license information.
     *
     * GET /api/v1/licenses/info
     */
    public function info(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'license_key' => ['required', 'string', 'regex:/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/'],
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

        // Check if license is soft deleted
        if ($license->trashed()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_REVOKED',
                    'message' => 'License key has been revoked',
                ],
            ], 422);
        }

        // Return license information (do not include notes)
        return response()->json([
            'success' => true,
            'data' => [
                'license_key_last4' => $license->key_last4,
                'license_model' => $license->license_model,
                'status' => $license->status->getValue(),
                'max_seats' => $license->max_seats,
                'expiry_date' => $license->expiry_date?->toIso8601String(),
                'customer_name' => $license->customer_name,
                'customer_email' => $license->customer_email,
                'product' => [
                    'name' => $product->name,
                    'slug' => $product->slug,
                ],
            ],
            'error' => null,
        ], 200);
    }

    /**
     * Transfer a license to a new device.
     *
     * POST /api/v1/licenses/transfer
     */
    public function transfer(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'license_key' => ['required', 'string', 'regex:/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/'],
            'device_fingerprint' => 'required|string',
            'user_identifier' => 'nullable|string',
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

        // Check if license is soft deleted
        if ($license->trashed()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LICENSE_REVOKED',
                    'message' => 'License key has been revoked',
                ],
            ], 422);
        }

        // Check if license is in inactive state
        if ($license->status->getValue() !== 'inactive') {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'TRANSFER_NOT_ALLOWED',
                    'message' => 'License must be in inactive state to transfer. Please contact admin to revoke current activation first.',
                ],
            ], 422);
        }

        try {
            // Perform new activation (transfer)
            $activation = $this->activationService->activate(
                $license,
                $validated['device_fingerprint'],
                $validated['user_identifier'] ?? null,
                $request->ip()
            );

            // Issue offline token
            $offlineToken = $this->offlineTokenService->issue($activation, $product);

            return response()->json([
                'success' => true,
                'data' => [
                    'offline_token' => $offlineToken,
                    'activation_id' => $activation->id,
                    'activated_at' => $activation->activated_at->toIso8601String(),
                    'license_model' => $license->license_model,
                    'expiry_date' => $license->expiry_date?->toIso8601String(),
                ],
                'error' => null,
            ], 200);
        } catch (\Exception $e) {
            $errorCode = $e->getMessage();
            $errorMessages = [
                'LICENSE_REVOKED' => 'License key has been revoked',
                'LICENSE_SUSPENDED' => 'License key has been suspended',
                'LICENSE_EXPIRED' => 'License key has expired',
                'DEVICE_MISMATCH' => 'Device fingerprint does not match',
                'USER_MISMATCH' => 'User identifier does not match',
                'SEATS_EXHAUSTED' => 'All seats are currently in use',
            ];

            $message = $errorMessages[$errorCode] ?? 'Transfer failed';

            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => $errorCode,
                    'message' => $message,
                ],
            ], 422);
        }
    }
}
