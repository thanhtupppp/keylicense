<?php

namespace Tests\Feature\Concerns;

use App\Models\AdminToken;
use App\Models\AdminUser;

final class AdminAuthFixtures
{
    public static function createAdmin(array $overrides = []): AdminUser
    {
        return AdminUser::query()->create(array_merge([
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'password_hash' => bcrypt('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ], $overrides));
    }

    public static function authHeaders(AdminUser $admin, array $overrides = []): array
    {
        $token = $overrides['token'] ?? bin2hex(random_bytes(20));
        $deviceKey = $overrides['device_key'] ?? 'test-device';
        $ipAddress = $overrides['ip_address'] ?? '127.0.0.1';
        $userAgent = $overrides['user_agent'] ?? 'Pest';
        $expiresAt = $overrides['expires_at'] ?? now()->addHour();

        AdminToken::query()->create([
            'admin_user_id' => $admin->id,
            'token_hash' => hash('sha256', $token),
            'device_key' => $deviceKey,
            'last_ip' => $ipAddress,
            'last_user_agent' => $userAgent,
            'last_activity_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        return ['Authorization' => 'Bearer '.$token];
    }
}
