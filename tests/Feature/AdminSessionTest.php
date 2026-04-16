<?php

use App\Models\AdminToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\AdminAuthFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('lists only active non-idle admin sessions', function (): void {
    Http::preventStrayRequests();

    $admin = AdminAuthFixtures::createAdmin();
    $currentToken = AdminToken::query()->create([
        'admin_user_id' => $admin->id,
        'token_hash' => hash('sha256', 'current-token'),
        'device_key' => 'device-current',
        'last_ip' => '127.0.0.1',
        'last_user_agent' => 'Pest',
        'last_activity_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    AdminToken::query()->create([
        'admin_user_id' => $admin->id,
        'token_hash' => hash('sha256', 'idle-token'),
        'device_key' => 'device-idle',
        'last_ip' => '127.0.0.1',
        'last_user_agent' => 'Pest',
        'last_activity_at' => now()->subHours(2),
        'expires_at' => now()->addHour(),
    ]);

    $this->actingAs($admin, 'admin')
        ->withHeaders(['Authorization' => 'Bearer current-token'])
        ->getJson('/api/v1/admin/sessions')
        ->assertSuccessful()
        ->assertJsonPath('data.admin.email', 'admin@example.com')
        ->assertJsonPath('data.session_count', 1)
        ->assertJsonCount(1, 'data.sessions')
        ->assertJsonPath('data.sessions.0.id', $currentToken->id)
        ->assertJsonPath('data.sessions.0.is_current', true)
        ->assertJsonPath('data.sessions.0.device_key', 'device-current');
});

it('revokes other admin sessions', function (): void {
    $admin = AdminAuthFixtures::createAdmin();

    $currentToken = AdminToken::query()->create([
        'admin_user_id' => $admin->id,
        'token_hash' => hash('sha256', 'current-token'),
        'device_key' => 'device-current',
        'last_ip' => '127.0.0.1',
        'last_user_agent' => 'Pest',
        'last_activity_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    $otherToken = AdminToken::query()->create([
        'admin_user_id' => $admin->id,
        'token_hash' => hash('sha256', 'other-token'),
        'device_key' => 'device-other',
        'last_ip' => '127.0.0.1',
        'last_user_agent' => 'Pest',
        'last_activity_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin, ['token' => 'current-token']))
        ->deleteJson('/api/v1/admin/sessions/revoke-others')
        ->assertSuccessful()
        ->assertJsonPath('data.admin.email', 'admin@example.com')
        ->assertJsonPath('data.revoked', true)
        ->assertJsonPath('data.revoked_count', 1);

    expect(AdminToken::query()->find($otherToken->id)?->revoked_at)->not->toBeNull();
    expect(AdminToken::query()->find($currentToken->id)?->revoked_at)->toBeNull();
});
