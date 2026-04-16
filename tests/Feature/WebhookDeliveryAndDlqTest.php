<?php

use App\Jobs\WebhookDeliveryJob;
use App\Models\FailedJobEntry;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\AdminAuthFixtures;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('webhook delivery job stores delivery metadata', function (): void {
    Http::fake([
        'https://example.com/webhook' => Http::response('ok', 200),
    ]);

    (new WebhookDeliveryJob(
        'https://example.com/webhook',
        'license.revoked',
        ['license_id' => 'lic-123'],
        'secret'
    ))->handle();

    $this->assertDatabaseHas('webhook_deliveries', [
        'event' => 'license.revoked',
        'status_code' => 200,
        'attempt_count' => 1,
    ]);
});

test('dlq api lists failed jobs', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    FailedJobEntry::query()->create([
        'uuid' => (string) Str::uuid(),
        'connection' => 'redis',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'boom',
        'failed_at' => now(),
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->getJson('/api/v1/admin/jobs/dlq')
        ->assertSuccessful()
        ->assertJsonPath('data.jobs.0.exception', 'boom');
});
