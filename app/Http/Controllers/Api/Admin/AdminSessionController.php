<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginHistory;
use App\Models\AdminToken;
use App\Models\AdminUser;
use App\Services\Billing\PlatformConfigService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminSessionController extends Controller
{
    public function __construct(private readonly PlatformConfigService $platformConfigService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        /** @var AdminToken|null $currentToken */
        $currentToken = $request->attributes->get('admin_token');

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        $idleTimeoutMinutes = (int) $this->platformConfigService->get('admin_session_idle_timeout_min', config('admin_portal.idle_timeout_minutes', 30));
        $idleCutoff = now()->subMinutes($idleTimeoutMinutes);

        $sessions = AdminToken::query()
            ->where('admin_user_id', $admin->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->where('last_activity_at', '>=', $idleCutoff)
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(function (AdminToken $token) use ($currentToken): array {
                return [
                    'id' => $token->id,
                    'device_key' => $token->device_key,
                    'ip_address' => $token->last_ip,
                    'location' => $this->resolveLocation($token->last_ip),
                    'user_agent' => $token->last_user_agent,
                    'last_active_at' => $token->last_activity_at?->toISOString(),
                    'expires_at' => $token->expires_at?->toISOString(),
                    'is_current' => $currentToken?->id === $token->id,
                ];
            })
            ->all();

        return ApiResponse::success([
            'admin' => $this->adminPayload($admin),
            'sessions' => $sessions,
            'session_count' => count($sessions),
            'current_session_id' => $currentToken?->id,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $admin = $this->admin($request);

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        $token = AdminToken::query()
            ->where('admin_user_id', $admin->id)
            ->whereKey($id)
            ->whereNull('revoked_at')
            ->first();

        if (! $token) {
            return ApiResponse::error('SESSION_NOT_FOUND', 'Session not found.', 404);
        }

        $token->forceFill(['revoked_at' => now()])->save();

        return ApiResponse::success(['revoked' => true]);
    }

    public function destroyOthers(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        /** @var AdminToken|null $currentToken */
        $currentToken = $request->attributes->get('admin_token');

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        $query = AdminToken::query()
            ->where('admin_user_id', $admin->id)
            ->whereNull('revoked_at');

        if ($currentToken) {
            $query->where('id', '!=', $currentToken->id);
        }

        $revokedCount = $query->update(['revoked_at' => now()]);

        return ApiResponse::success([
            'admin' => [
                'id' => $admin->id,
                'email' => $admin->email,
                'full_name' => $admin->full_name,
            ],
            'revoked' => true,
            'revoked_count' => $revokedCount,
        ]);
    }

    public function loginHistory(Request $request): JsonResponse
    {
        $admin = $this->admin($request);

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        $history = AdminLoginHistory::query()
            ->where('admin_id', $admin->id)
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get()
            ->map(static function (AdminLoginHistory $item): array {
                return [
                    'id' => $item->id,
                    'ip_address' => $item->ip_address,
                    'location' => $item->location,
                    'user_agent' => $item->user_agent,
                    'success' => $item->success,
                    'failure_reason' => $item->failure_reason,
                    'occurred_at' => $item->occurred_at?->toISOString(),
                ];
            })
            ->all();

        return ApiResponse::success([
            'admin' => $this->adminPayload($admin),
            'history' => $history,
            'history_count' => count($history),
        ]);
    }

    public function unlock(Request $request, string $userId): JsonResponse
    {
        $actor = $this->admin($request);

        if (! $actor) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        if ($actor->role !== 'super_admin') {
            return ApiResponse::error('FORBIDDEN', 'Only super admin can unlock accounts.', 403);
        }

        $target = AdminUser::query()->find($userId);

        if (! $target) {
            return ApiResponse::error('ADMIN_NOT_FOUND', 'Admin user not found.', 404);
        }

        $target->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        return ApiResponse::success([
            'admin' => $this->adminPayload($actor),
            'unlocked' => true,
        ]);
    }

    public function forceLogout(Request $request, string $userId): JsonResponse
    {
        $actor = $this->admin($request);

        if (! $actor) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        if ($actor->role !== 'super_admin') {
            return ApiResponse::error('FORBIDDEN', 'Only super admin can force logout users.', 403);
        }

        $target = AdminUser::query()->find($userId);

        if (! $target) {
            return ApiResponse::error('ADMIN_NOT_FOUND', 'Admin user not found.', 404);
        }

        $revokedCount = AdminToken::query()
            ->where('admin_user_id', $target->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return ApiResponse::success([
            'admin' => $this->adminPayload($target),
            'revoked' => true,
            'revoked_count' => $revokedCount,
        ]);
    }

    public function listUserSessions(Request $request, string $userId): JsonResponse
    {
        $actor = $this->admin($request);

        if (! $actor) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        if ($actor->role !== 'super_admin') {
            return ApiResponse::error('FORBIDDEN', 'Only super admin can view other sessions.', 403);
        }

        $target = AdminUser::query()->find($userId);

        if (! $target) {
            return ApiResponse::error('ADMIN_NOT_FOUND', 'Admin user not found.', 404);
        }

        $idleTimeoutMinutes = (int) $this->platformConfigService->get('admin_session_idle_timeout_min', config('admin_portal.idle_timeout_minutes', 30));

        $sessions = AdminToken::query()
            ->where('admin_user_id', $target->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->where('last_activity_at', '>=', now()->subMinutes($idleTimeoutMinutes))
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(static fn (AdminToken $token): array => [
                'id' => $token->id,
                'device_key' => $token->device_key,
                'ip_address' => $token->last_ip,
                'location' => null,
                'user_agent' => $token->last_user_agent,
                'last_active_at' => $token->last_activity_at?->toISOString(),
                'expires_at' => $token->expires_at?->toISOString(),
            ])
            ->all();

        return ApiResponse::success([
            'admin' => $this->adminPayload($target),
            'sessions' => $sessions,
            'session_count' => count($sessions),
        ]);
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

    private function resolveLocation(?string $ipAddress): ?string
    {
        return $ipAddress ? 'Unknown' : null;
    }
}
