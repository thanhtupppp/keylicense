<?php

namespace App\Http\Controllers\Api\Client;

use App\Actions\Licensing\ActivateLicenseAction;
use App\Actions\Licensing\ConfirmOfflineChallengeAction;
use App\Actions\Licensing\DeactivateLicenseAction;
use App\Actions\Licensing\RequestOfflineChallengeAction;
use App\Actions\Licensing\ValidateLicenseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ActivateLicenseRequest;
use App\Http\Requests\Client\ConfirmOfflineChallengeRequest;
use App\Http\Requests\Client\DeactivateLicenseRequest;
use App\Http\Requests\Client\RequestOfflineChallengeRequest;
use App\Http\Requests\Client\ValidateLicenseRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LicenseClientController extends Controller
{
    public function __construct(
        private readonly ActivateLicenseAction $activateLicenseAction,
        private readonly ValidateLicenseAction $validateLicenseAction,
        private readonly DeactivateLicenseAction $deactivateLicenseAction,
        private readonly RequestOfflineChallengeAction $requestOfflineChallengeAction,
        private readonly ConfirmOfflineChallengeAction $confirmOfflineChallengeAction,
    ) {
    }

    public function activate(ActivateLicenseRequest $request): JsonResponse
    {
        $result = $this->activateLicenseAction->execute($request->validated());

        if (! $result->success) {
            return ApiResponse::error($result->status ?? 'activation_failed', $result->message ?? 'License activation failed.', 422, $result->payload);
        }

        return ApiResponse::success([
            'status' => $result->status,
            'message' => $result->message,
            'activation_id' => $result->activationId,
            'license_status' => $result->payload['license_status'] ?? null,
            'payload' => $result->payload,
        ]);
    }

    public function validateLicense(ValidateLicenseRequest $request): JsonResponse
    {
        $result = $this->validateLicenseAction->execute($request->validated());

        if (! $result->valid) {
            $statusCode = \in_array($result->status, ['inactive', 'activation_inactive', 'deactivated', 'ACTIVATION_DEACTIVATED'], true) ? 403 : 422;

            return ApiResponse::error($result->status ?? 'validation_failed', $result->message ?? 'License validation failed.', $statusCode, $result->payload);
        }

        return ApiResponse::success([
            'status' => $result->status,
            'message' => $result->message,
            'license' => $result->payload,
        ]);
    }

    public function deactivate(DeactivateLicenseRequest $request): JsonResponse
    {
        $result = $this->deactivateLicenseAction->execute($request->validated());

        if (! $result->success) {
            return ApiResponse::error($result->status ?? 'deactivation_failed', $result->message ?? 'License deactivation failed.', 422, $result->payload);
        }

        return ApiResponse::success([
            'status' => $result->status,
            'message' => $result->message,
            'activation_id' => $result->activationId,
            'payload' => $result->payload,
        ]);
    }

    public function requestOfflineChallenge(RequestOfflineChallengeRequest $request): JsonResponse
    {
        $result = $this->requestOfflineChallengeAction->execute($request->validated());

        if (! $result->issued) {
            return ApiResponse::error('challenge_failed', $result->payload['message'] ?? 'Offline challenge request failed.', 422, $result->payload);
        }

        return ApiResponse::success([
            'status' => $result->payload['status'] ?? 'challenge_issued',
            'message' => $result->payload['message'] ?? null,
            'challenge_id' => $result->challengeId,
            'expires_at' => $result->expiresAt,
            'payload' => $result->payload,
        ]);
    }

    public function confirmOfflineChallenge(ConfirmOfflineChallengeRequest $request): JsonResponse
    {
        $result = $this->confirmOfflineChallengeAction->execute($request->validated());

        if (! $result->issued) {
            return ApiResponse::error('challenge_failed', $result->payload['message'] ?? 'Offline challenge confirmation failed.', 422, $result->payload);
        }

        return ApiResponse::success([
            'status' => $result->payload['status'] ?? 'challenge_confirmed',
            'message' => $result->payload['message'] ?? null,
            'challenge_id' => $result->challengeId,
            'expires_at' => $result->expiresAt,
            'payload' => $result->payload,
        ]);
    }
}
