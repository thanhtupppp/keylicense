<?php

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AdminAuthFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('issues an api key for super admin', function (): void {
    $admin = AdminAuthFixtures::createAdmin();

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/api-keys', [
            'name' => 'Client Key',
            'scope' => 'client',
        ])
        ->assertCreated()
        ->assertJsonPath('data.api_key.name', 'Client Key')
        ->assertJsonPath('data.api_key.scope', 'client')
        ->assertJsonStructure(['data' => ['api_key' => ['id', 'name', 'scope', 'is_active', 'plain_text_key']]]);

    expect(ApiKey::query()->count())->toBe(1);
});

it('rotates an api key', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $apiKey = ApiKey::query()->create([
        'name' => 'Client Key',
        'api_key' => hash('sha256', 'old-key'),
        'scope' => 'client',
        'is_active' => true,
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/api-keys/'.$apiKey->id.'/rotate')
        ->assertSuccessful()
        ->assertJsonPath('data.api_key.id', $apiKey->id)
        ->assertJsonPath('data.api_key.is_active', true)
        ->assertJsonStructure(['data' => ['api_key' => ['id', 'name', 'scope', 'is_active', 'plain_text_key']]]);
});

it('revokes an api key', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $apiKey = ApiKey::query()->create([
        'name' => 'Client Key',
        'api_key' => hash('sha256', 'old-key'),
        'scope' => 'client',
        'is_active' => true,
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->deleteJson('/api/v1/admin/api-keys/'.$apiKey->id)
        ->assertSuccessful()
        ->assertJsonPath('data.api_key.id', $apiKey->id)
        ->assertJsonPath('data.api_key.is_active', false);

    $this->assertDatabaseHas('api_keys', [
        'id' => $apiKey->id,
        'is_active' => false,
    ]);
});
