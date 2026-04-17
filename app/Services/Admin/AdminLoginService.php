<?php

namespace App\Services\Admin;

use App\Models\AdminLoginHistory;
use App\Models\AdminToken;
use App\Models\AdminTokenAuditLog;
use App\Models\AdminUser;
use App\Services\Billing\PlatformConfigService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminLoginService
{
    private ?bool $hasSessionSecurityColumns = null;

    public function __construct(
        private readonly AdminMfaService $mfaService = new AdminMfaService(),
        private readonly PlatformConfigService $platformConfigService = new PlatformConfigService(),
    ) {
    }

    public function login(string $email, string $password, bool $remember = false, ?string $deviceKey = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        /** @var AdminUser|null $admin */
        $admin = AdminUser::query()->where('email', $email)->first();
        if (! $admin || ! Hash::check($password, $admin->password_hash)) {
            if ($admin) {
                $this->increaseFailedLoginAttempts($admin);
                AdminLoginHistory::query()->create(['admin_id' => $admin->id, 'ip_address' => $ipAddress, 'user_agent' => $userAgent, 'location' => null, 'success' => false, 'failure_reason' => 'wrong_password', 'occurred_at' => now()]);
            }
            return ['error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Invalid credentials.', 'status' => 401]];
        }

        if ($this->isAdminLocked($admin)) {
            return ['error' => ['code' => 'ACCOUNT_LOCKED', 'message' => 'Admin account is temporarily locked. Please try again later.', 'status' => 423]];
        }

        if (! $admin->is_active) {
            return ['error' => ['code' => 'ACCOUNT_INACTIVE', 'message' => 'Admin account is inactive.', 'status' => 403]];
        }

        $ttl = $remember ? config('admin_portal.session_seconds_remember', 2592000) : config('admin_portal.session_seconds_default', 7200);
        $device = $deviceKey ?: 'device_'.Str::lower(Str::random(20));
        $plainToken = Str::random(64);
        $token = AdminToken::query()->create(['admin_user_id' => $admin->id, 'token_hash' => hash('sha256', $plainToken), 'device_key' => $device, 'last_ip' => $ipAddress, 'last_user_agent' => $userAgent, 'last_activity_at' => now(), 'expires_at' => now()->addSeconds($ttl)]);
        $this->audit($admin->id, $token->id, 'login', 'admin', $admin->id, $ipAddress, $userAgent, ['remember' => $remember, 'expires_in' => $ttl, 'device_key' => $device]);
        $this->resetLockFields($admin);
        $admin->forceFill(['api_token' => hash('sha256', $plainToken)])->save();

        return ['token' => $plainToken, 'token_id' => $token->id, 'admin' => ['id' => $admin->id, 'email' => $admin->email, 'full_name' => $admin->full_name], 'expires_in' => $ttl, 'expires_at' => $token->expires_at->toISOString(), 'kicked_count' => 0];
    }

    public function revokeByPlainToken(string $plainToken, ?string $actorAdminUserId = null, ?string $ipAddress = null, ?string $userAgent = null, string $event = 'revoke'): void
    {
        $token = AdminToken::query()->where('token_hash', hash('sha256', $plainToken))->first();
        if (! $token) {
            return;
        }
        $token->forceFill(['revoked_at' => now()])->save();
        $this->audit($token->admin_user_id, $token->id, $event, $actorAdminUserId ? 'admin' : 'system', $actorAdminUserId, $ipAddress, $userAgent, ['source' => 'portal_logout']);
    }

    private function audit(string $adminUserId, ?string $adminTokenId, string $event, string $actorType, ?string $actorAdminUserId, ?string $ipAddress, ?string $userAgent, array $metadata): void
    {
        AdminTokenAuditLog::query()->create(['admin_user_id' => $adminUserId, 'admin_token_id' => $adminTokenId, 'event' => $event, 'actor_type' => $actorType, 'actor_admin_user_id' => $actorAdminUserId, 'ip_address' => $ipAddress, 'user_agent' => $userAgent, 'metadata' => $metadata, 'created_at' => now()]);
    }

    private function increaseFailedLoginAttempts(AdminUser $admin): void
    {
        if (! $this->supportsSessionSecurityColumns()) {
            return;
        }
        $admin->forceFill(['failed_login_attempts' => ((int) ($admin->failed_login_attempts ?? 0)) + 1, 'locked_until' => null])->save();
    }

    private function isAdminLocked(AdminUser $admin): bool
    {
        if (! $this->supportsSessionSecurityColumns()) {
            return false;
        }
        return $admin->locked_until !== null && now()->lessThan($admin->locked_until);
    }

    private function resetLockFields(AdminUser $admin): void
    {
        if (! $this->supportsSessionSecurityColumns()) {
            return;
        }
        $admin->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
    }

    private function supportsSessionSecurityColumns(): bool
    {
        if ($this->hasSessionSecurityColumns !== null) {
            return $this->hasSessionSecurityColumns;
        }
        return $this->hasSessionSecurityColumns = Schema::hasColumns('admin_users', ['failed_login_attempts', 'locked_until']);
    }
}
