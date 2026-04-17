<?php

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows active admin access gate', function (): void {
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin One',
        'email' => 'admin@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'admin',
        'is_active' => true,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
    ]);

    expect(Gate::forUser($admin)->allows('admin-access'))->toBeTrue();
});

it('rejects inactive admin access gate', function (): void {
    $admin = AdminUser::query()->create([
        'full_name' => 'Admin Two',
        'email' => 'admin2@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'admin',
        'is_active' => false,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
    ]);

    expect(Gate::forUser($admin)->allows('admin-access'))->toBeFalse();
});

it('enforces license management gate by role', function (): void {
    $support = AdminUser::query()->create([
        'full_name' => 'Support',
        'email' => 'support@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'support',
        'is_active' => true,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
    ]);

    expect(Gate::forUser($support)->allows('admin-license-manage'))->toBeFalse();
});

it('allows entitlement management for support role', function (): void {
    $support = AdminUser::query()->create([
        'full_name' => 'Support',
        'email' => 'support2@example.com',
        'password_hash' => bcrypt('password'),
        'role' => 'support',
        'is_active' => true,
        'failed_login_attempts' => 0,
        'mfa_enabled' => false,
    ]);

    expect(Gate::forUser($support)->allows('admin-entitlement-manage'))->toBeTrue();
});
