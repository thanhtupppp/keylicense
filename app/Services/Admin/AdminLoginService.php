<?php

namespace App\Services\Admin;

use App\Models\AdminLoginHistory;
use App\Models\AdminToken;
use App\Models\AdminTokenAuditLog;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminLoginService
{
    private ?bool $hasSessionSecurityColumns = null;

    /**
     * @return array{
     *   token:string,
     *   token_id:string,
     *   admin:array{id:string,email:string,full_name:string},
     *   expires_in:int,
     *   expires_at:string,
     *   kicked_count:int
     * }|array{error:array{code:string,message:string,status:int}}
     */
    public function login(
        string $email,
        string $password,
        bool $remember = false,
        ?string $deviceKey = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): array
    {
        $admin = AdminUser::query()->where('email', $email)->first();

        if (! $admin || ! Hash::check($password, $admin->password_hash)) {
            if ($admin) {
                $this->increaseFailedLoginAttempts($admin);

                AdminLoginHistory::query()->create([
                    'admin_id' => $admin->id,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'location' => null,
                    'success' => false,
                    'failure_reason' => 'wrong_password',
                    'occurred_at' => now(),
                ]);
            }

            return [
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid credentials.',
                    'status' => 401,
                ],
            ];
        }

        if ($this->isAdminLocked($admin)) {
            AdminLoginHistory::query()->create([
                'admin_id' => $admin->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'location' => null,
                'success' => false,
                'failure_reason' => 'account_locked',
                'occurred_at' => now(),
            ]);

            return [
                'error' => [
                    'code' => 'ACCOUNT_LOCKED',
                    'message' => 'Admin account is temporarily locked. Please try again later.',
                    'status' => 423,
                ],
            ];
        }

        if (! $admin->is_active) {
            return [
                'error' => [
                    'code' => 'ACCOUNT_INACTIVE',
                    'message' => 'Admin account is inactive.',
                    'status' => 403,
                ],
            ];
        }

        $ttl = $remember
            ? config('admin_portal.session_seconds_remember', 2592000)
            : config('admin_portal.session_seconds_default', 7200);

        $device = $deviceKey ?: 'device_'.Str::lower(Str::random(20));

        // Revoke existing token for same admin+device first.
        AdminToken::query()
            ->where('admin_user_id', $admin->id)
            ->where('device_key', $device)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        // Enforce max concurrent devices.
        $maxDevices = config('admin_portal.max_devices', 2);
        $activeTokens = AdminToken::query()
            ->where('admin_user_id', $admin->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderBy('last_activity_at', 'asc')
            ->get();

        $kickedCount = 0;

        if ($activeTokens->count() >= $maxDevices) {
            $toRevoke = $activeTokens->take(($activeTokens->count() - $maxDevices) + 1);
            foreach ($toRevoke as $token) {
                if (! $this->assertModelOrLog($token, 'kick_over_device_limit', [
                    'admin_user_id' => $admin->id,
                ])) {
                    continue;
                }

                AdminToken::query()->whereKey($token->id)->update(['revoked_at' => now()]);
                $this->audit(
                    adminUserId: $admin->id,
                    adminTokenId: $token->id,
                    event: 'kick',
                    actorType: 'system',
                    actorAdminUserId: null,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    metadata: ['reason' => 'device_limit_exceeded']
                );
                $kickedCount++;
            }
        }

        $plainToken = Str::random(64);
        $token = AdminToken::query()->create([
            'admin_user_id' => $admin->id,
            'token_hash' => hash('sha256', $plainToken),
            'device_key' => $device,
            'last_ip' => $ipAddress,
            'last_user_agent' => $userAgent,
            'last_activity_at' => now(),
            'expires_at' => now()->addSeconds($ttl),
        ]);

        $this->audit(
            adminUserId: $admin->id,
            adminTokenId: $token->id,
            event: 'login',
            actorType: 'admin',
            actorAdminUserId: $admin->id,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            metadata: [
                'remember' => $remember,
                'expires_in' => $ttl,
                'device_key' => $device,
            ]
        );

        $this->resetLockFields($admin);

        AdminLoginHistory::query()->create([
            'admin_id' => $admin->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'location' => null,
            'success' => true,
            'failure_reason' => null,
            'occurred_at' => now(),
        ]);

        // Backward-compatible field (legacy middleware paths)
        $admin->forceFill([
            'api_token' => hash('sha256', $plainToken),
        ])->save();

        return [
            'token' => $plainToken,
            'token_id' => $token->id,
            'admin' => [
                'id' => $admin->id,
                'email' => $admin->email,
                'full_name' => $admin->full_name,
            ],
            'expires_in' => $ttl,
            'expires_at' => $token->expires_at->toISOString(),
            'kicked_count' => $kickedCount,
        ];
    }

    public function revokeByPlainToken(
        string $plainToken,
        ?string $actorAdminUserId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $event = 'revoke',
    ): void {
        $hash = hash('sha256', $plainToken);

        $token = AdminToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->first();

        if ($token) {
            if (! $this->assertModelOrLog($token, 'revoke_by_plain_token', ['event' => $event])) {
                return;
            }

            AdminToken::query()->whereKey($token->id)->update(['revoked_at' => now()]);

            $this->audit(
                adminUserId: $token->admin_user_id,
                adminTokenId: $token->id,
                event: $event,
                actorType: $actorAdminUserId ? 'admin' : 'system',
                actorAdminUserId: $actorAdminUserId,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                metadata: []
            );
        }

        AdminUser::query()->where('api_token', $hash)->update(['api_token' => null]);
    }

    private function audit(
        string $adminUserId,
        ?string $adminTokenId,
        string $event,
        string $actorType,
        ?string $actorAdminUserId,
        ?string $ipAddress,
        ?string $userAgent,
        array $metadata,
    ): void {
        AdminTokenAuditLog::query()->create([
            'admin_user_id' => $adminUserId,
            'admin_token_id' => $adminTokenId,
            'event' => $event,
            'actor_type' => $actorType,
            'actor_admin_user_id' => $actorAdminUserId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  mixed  $candidate
     */
    private function assertModelOrLog(mixed $candidate, string $context, array $meta = []): bool
    {
        if ($candidate instanceof Model) {
            return true;
        }

        Log::warning('Admin token hardening guard failed: expected Eloquent model.', [
            'context' => $context,
            'actual_type' => get_debug_type($candidate),
            'meta' => $meta,
        ]);

        return false;
    }

    private function increaseFailedLoginAttempts(AdminUser $admin): void
    {
        if (! $this->supportsSessionSecurityColumns()) {
            return;
        }

        $failedAttempts = ((int) ($admin->failed_login_attempts ?? 0)) + 1;
        $lockThreshold = (int) config('admin_portal.max_login_attempts', 5);
        $lockedUntil = $failedAttempts >= $lockThreshold ? now()->addMinutes(15) : null;

        $admin->forceFill([
            'failed_login_attempts' => $failedAttempts,
            'locked_until' => $lockedUntil,
        ])->save();
    }

    private function isAdminLocked(AdminUser $admin): bool
    {
        if (! $this->supportsSessionSecurityColumns()) {
            return false;
        }

        $lockedUntil = $admin->locked_until;

        return $lockedUntil !== null && now()->lessThan($lockedUntil);
    }

    private function resetLockFields(AdminUser $admin): void
    {
        if (! $this->supportsSessionSecurityColumns()) {
            return;
        }

        $admin->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    private function supportsSessionSecurityColumns(): bool
    {
        if ($this->hasSessionSecurityColumns !== null) {
            return $this->hasSessionSecurityColumns;
        }

        $this->hasSessionSecurityColumns = Schema::hasColumns('admin_users', [
            'failed_login_attempts',
            'locked_until',
        ]);

        return $this->hasSessionSecurityColumns;
    }
}
