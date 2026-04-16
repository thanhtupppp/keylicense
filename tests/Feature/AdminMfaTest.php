<?php

use App\Models\AdminMfaBackupCode;
use App\Models\AdminUser;
use App\Services\Admin\AdminMfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('lets admin setup mfa', function (): void {
    Http::preventStrayRequests();
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin User',
        'email' => 'admin@example.com',
        'password_hash' => Hash::make('password123'),
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/auth/mfa/setup')
        ->assertSuccessful()
        ->assertJsonPath('data.admin.email', 'admin@example.com')
        ->assertJsonPath('data.mfa_enabled', false)
        ->assertJsonStructure(['data' => ['admin', 'secret', 'otpauth_uri', 'backup_codes']]);

    expect(AdminMfaBackupCode::query()->where('admin_user_id', $admin->id)->count())->toBe(10);
});

it('lets admin verify mfa setup', function (): void {
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin User',
        'email' => 'admin@example.com',
        'password_hash' => Hash::make('password123'),
        'role' => 'super_admin',
        'is_active' => true,
        'mfa_secret' => 'shared-secret',
    ]);

    app(AdminMfaService::class)->setup($admin);

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/auth/mfa/verify-setup', ['code' => 'shared-secret'])
        ->assertSuccessful()
        ->assertJsonPath('data.admin.email', 'admin@example.com')
        ->assertJsonPath('data.valid', true);

    $this->assertDatabaseHas('admin_users', [
        'id' => $admin->id,
        'mfa_enabled' => true,
    ]);
});

it('returns mfa required on admin login when enabled', function (): void {
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin User',
        'email' => 'admin@example.com',
        'password_hash' => Hash::make('password123'),
        'role' => 'super_admin',
        'is_active' => true,
        'mfa_enabled' => true,
        'mfa_secret' => 'shared-secret',
    ]);

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => $admin->email,
        'password' => 'password123',
    ])->assertSuccessful()
      ->assertJsonPath('data.mfa_required', true)
      ->assertJsonPath('data.admin.email', 'admin@example.com')
      ->assertJsonStructure(['data' => ['mfa_token', 'mfa_expires_in', 'admin']]);
});

it('lets admin challenge mfa and get session', function (): void {
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin User',
        'email' => 'admin@example.com',
        'password_hash' => Hash::make('password123'),
        'role' => 'super_admin',
        'is_active' => true,
        'mfa_enabled' => true,
        'mfa_secret' => 'shared-secret',
    ]);

    cache()->put('admin:mfa:test-mfa-token', [
        'admin_id' => $admin->id,
        'device_key' => 'device-1',
        'remember' => false,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ], now()->addMinutes(10));

    $this->postJson('/api/v1/admin/auth/mfa/challenge', [
        'code' => 'shared-secret',
        'mfa_token' => 'test-mfa-token',
    ])->assertSuccessful()
      ->assertJsonPath('data.admin.email', 'admin@example.com')
      ->assertJsonPath('data.kicked_count', 0)
      ->assertJsonStructure(['data' => ['token', 'token_id', 'admin', 'expires_in', 'expires_at', 'kicked_count']]);
});

it('lets admin disable mfa', function (): void {
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin User',
        'email' => 'admin@example.com',
        'password_hash' => Hash::make('password123'),
        'role' => 'super_admin',
        'is_active' => true,
        'mfa_enabled' => true,
        'mfa_secret' => 'shared-secret',
    ]);

    app(AdminMfaService::class)->setup($admin);

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/auth/mfa/disable', ['code' => 'shared-secret'])
        ->assertSuccessful()
        ->assertJsonPath('data.admin.email', 'admin@example.com')
        ->assertJsonPath('data.valid', true);

    $this->assertDatabaseHas('admin_users', [
        'id' => $admin->id,
        'mfa_enabled' => false,
    ]);
});

it('lets admin regenerate backup codes', function (): void {
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin User',
        'email' => 'admin@example.com',
        'password_hash' => Hash::make('password123'),
        'role' => 'super_admin',
        'is_active' => true,
        'mfa_enabled' => true,
        'mfa_secret' => 'shared-secret',
    ]);

    app(AdminMfaService::class)->setup($admin);

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/auth/mfa/regenerate-backup-codes', ['code' => 'shared-secret'])
        ->assertSuccessful()
        ->assertJsonPath('data.admin.email', 'admin@example.com')
        ->assertJsonPath('data.valid', true)
        ->assertJsonCount(10, 'data.backup_codes');
});
