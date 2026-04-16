<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Admin\AdminLoginService;
use App\Services\Admin\AdminMfaService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MfaController extends Controller
{
    public function setup(Request $request, AdminMfaService $mfaService): JsonResponse
    {
        $admin = $this->admin($request);

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        return ApiResponse::success([
            'admin' => $this->adminPayload($admin),
            ...$mfaService->setup($admin)->toArray(),
        ]);
    }

    public function verifySetup(Request $request, AdminMfaService $mfaService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $admin = $this->admin($request);

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        $result = $mfaService->verifySetup($admin, $payload['code']);

        return $result->valid
            ? ApiResponse::success([
                'admin' => $this->adminPayload($admin),
                ...$result->toArray(),
            ])
            : ApiResponse::error('MFA_INVALID', 'Invalid MFA code.', 422);
    }

    public function challenge(Request $request, AdminLoginService $adminLoginService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string'],
            'mfa_token' => ['required', 'string'],
        ]);

        $result = $adminLoginService->challengeMfa($payload['mfa_token'], $payload['code']);

        if (isset($result['error'])) {
            return ApiResponse::error(
                $result['error']['code'],
                $result['error']['message'],
                $result['error']['status']
            );
        }

        if (isset($result['admin']) && $result['admin'] instanceof AdminUser) {
            $result['admin'] = $this->adminPayload($result['admin']);
        }

        return ApiResponse::success([
            'admin' => $result['admin'] ?? null,
            'token' => $result['token'] ?? null,
            'token_id' => $result['token_id'] ?? null,
            'expires_in' => $result['expires_in'] ?? null,
            'expires_at' => $result['expires_at'] ?? null,
            'kicked_count' => $result['kicked_count'] ?? 0,
        ]);
    }

    public function disable(Request $request, AdminMfaService $mfaService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $admin = $this->admin($request);

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        $result = $mfaService->disable($admin, $payload['code']);

        return $result->valid
            ? ApiResponse::success([
                'admin' => $this->adminPayload($admin),
                ...$result->toArray(),
            ])
            : ApiResponse::error('MFA_INVALID', 'Invalid MFA code.', 422);
    }

    public function regenerateBackupCodes(Request $request, AdminMfaService $mfaService): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $admin = $this->admin($request);

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        $result = $mfaService->regenerateBackupCodes($admin, $payload['code']);

        return $result->valid
            ? ApiResponse::success([
                'admin' => $this->adminPayload($admin),
                ...$result->toArray(),
            ])
            : ApiResponse::error('MFA_INVALID', 'Invalid MFA code.', 422);
    }

    private function admin(Request $request): ?AdminUser
    {
        $admin = $request->attributes->get('admin_user') ?? $request->user('admin') ?? $request->user();

        return $admin instanceof AdminUser ? $admin : null;
    }

    private function adminPayload(AdminUser $admin): array
    {
        return [
            'id' => $admin->id,
            'email' => $admin->email,
            'full_name' => $admin->full_name,
        ];
    }
}
