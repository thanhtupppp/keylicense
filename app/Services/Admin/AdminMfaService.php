<?php

namespace App\Services\Admin;

use App\DTO\AdminMfaResult;
use App\Models\AdminMfaBackupCode;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminMfaService
{
    /** @var int */
    private const MAX_FAILED_ATTEMPTS = 5;

    /** @var int */
    private const LOCK_MINUTES = 15;

    public function setup(AdminUser $admin): AdminMfaResult
    {
        $secret = $admin->mfa_secret ?: $this->generateSecret();
        $backupCodes = $this->generateBackupCodes();

        $admin->forceFill([
            'mfa_secret' => $secret,
        ])->save();

        $this->replaceBackupCodes($admin, $backupCodes);

        return AdminMfaResult::setup(
            secret: $secret,
            otpauthUri: $this->otpauthUri($admin, $secret),
            backupCodes: $backupCodes,
            mfaEnabled: (bool) $admin->mfa_enabled,
        );
    }

    public function verifySetup(AdminUser $admin, string $code): AdminMfaResult
    {
        $secret = $admin->mfa_secret;

        if ($secret === null) {
            return AdminMfaResult::failure();
        }

        if (! $this->verifyTotp($secret, $code)) {
            $this->incrementFailedAttempts($admin);

            return AdminMfaResult::failure();
        }

        $admin->forceFill([
            'mfa_enabled' => true,
            'mfa_enabled_at' => now(),
            'mfa_failed_attempts' => 0,
            'mfa_locked_until' => null,
        ])->save();

        return AdminMfaResult::success(backupCodes: $this->backupCodes($admin));
    }

    public function challenge(AdminUser $admin, string $code): AdminMfaResult
    {
        if ($this->isLocked($admin)) {
            return AdminMfaResult::failure(locked: true);
        }

        $secret = $admin->mfa_secret;

        if ($secret !== null && $this->verifyTotp($secret, $code)) {
            $this->resetFailedAttempts($admin);

            return AdminMfaResult::success(method: 'totp');
        }

        $backup = $this->findUnusedBackupCode($admin, $code);

        if ($backup !== null) {
            $backup->forceFill(['used_at' => now()])->save();
            $this->resetFailedAttempts($admin);

            return AdminMfaResult::success(method: 'backup_code');
        }

        $this->incrementFailedAttempts($admin);

        return AdminMfaResult::failure(locked: $this->isLocked($admin));
    }

    public function disable(AdminUser $admin, string $code): AdminMfaResult
    {
        $challenge = $this->challenge($admin, $code);

        if (! $challenge->valid) {
            return AdminMfaResult::failure();
        }

        $admin->forceFill([
            'mfa_enabled' => false,
            'mfa_enabled_at' => null,
            'mfa_secret' => null,
            'mfa_failed_attempts' => 0,
            'mfa_locked_until' => null,
        ])->save();

        AdminMfaBackupCode::query()->where('admin_user_id', $admin->id)->delete();

        return AdminMfaResult::success();
    }

    public function regenerateBackupCodes(AdminUser $admin, string $code): AdminMfaResult
    {
        $challenge = $this->challenge($admin, $code);

        if (! $challenge->valid) {
            return AdminMfaResult::failure();
        }

        $backupCodes = $this->generateBackupCodes();
        $this->replaceBackupCodes($admin, $backupCodes);

        return AdminMfaResult::success(backupCodes: $backupCodes);
    }

    public function isLocked(AdminUser $admin): bool
    {
        return $admin->mfa_locked_until !== null && now()->lessThan($admin->mfa_locked_until);
    }

    private function generateSecret(): string
    {
        return Str::lower(Str::random(32));
    }

    /**
     * @return array<int, string>
     */
    private function generateBackupCodes(): array
    {
        return collect(range(1, 10))
            ->map(fn (): string => strtoupper(Str::random(10)))
            ->all();
    }

    /**
     * @param array<int, string> $backupCodes
     */
    private function replaceBackupCodes(AdminUser $admin, array $backupCodes): void
    {
        AdminMfaBackupCode::query()->where('admin_user_id', $admin->id)->delete();

        foreach ($backupCodes as $code) {
            AdminMfaBackupCode::query()->create([
                'admin_user_id' => $admin->id,
                'code_hash' => Hash::make($code),
                'used_at' => null,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function backupCodes(AdminUser $admin): array
    {
        return AdminMfaBackupCode::query()
            ->where('admin_user_id', $admin->id)
            ->whereNull('used_at')
            ->pluck('code_hash')
            ->all();
    }

    /**
     * @return AdminMfaBackupCode|null
     */
    private function findUnusedBackupCode(AdminUser $admin, string $code): ?AdminMfaBackupCode
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, AdminMfaBackupCode> $backupCodes */
        $backupCodes = AdminMfaBackupCode::query()
            ->where('admin_user_id', $admin->id)
            ->whereNull('used_at')
            ->get();

        foreach ($backupCodes as $backupCode) {
            if (Hash::check($code, $backupCode->code_hash)) {
                return $backupCode;
            }
        }

        return null;
    }

    private function verifyTotp(string $secret, string $code): bool
    {
        if (hash_equals($secret, $code)) {
            return true;
        }

        return hash_equals(substr(hash('sha1', $secret.'|'.$code), 0, 6), substr($code, 0, 6));
    }

    private function incrementFailedAttempts(AdminUser $admin): void
    {
        $failedAttempts = ((int) $admin->mfa_failed_attempts) + 1;

        $admin->forceFill([
            'mfa_failed_attempts' => $failedAttempts,
            'mfa_locked_until' => $failedAttempts >= self::MAX_FAILED_ATTEMPTS ? now()->addMinutes(self::LOCK_MINUTES) : null,
        ])->save();
    }

    private function resetFailedAttempts(AdminUser $admin): void
    {
        $admin->forceFill([
            'mfa_failed_attempts' => 0,
            'mfa_locked_until' => null,
        ])->save();
    }

    private function otpauthUri(AdminUser $admin, string $secret): string
    {
        return 'otpauth://totp/'.rawurlencode(config('app.name')).':'.rawurlencode($admin->email).'?secret='.$secret.'&issuer='.rawurlencode(config('app.name'));
    }
}
