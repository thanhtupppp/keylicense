<?php

namespace Tests\Support;

use App\Models\AdminToken;
use App\Models\AdminUser;

final class PortalFixtures
{
    public static function createAdmin(): AdminUser
    {
        return AdminUser::query()->create([
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'password_hash' => bcrypt('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public static function sessionHeaders(AdminUser $admin): array
    {
        $token = bin2hex(random_bytes(20));

        AdminToken::query()->create([
            'admin_user_id' => $admin->id,
            'token_hash' => hash('sha256', $token),
            'device_key' => 'test-device',
            'last_ip' => '127.0.0.1',
            'last_user_agent' => 'Pest',
            'last_activity_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        return [
            'Authorization' => "Bearer {$token}",
            'X-Admin-Session' => $token,
        ];
    }
}
