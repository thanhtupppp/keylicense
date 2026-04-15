<?php

use App\Models\MaintenanceWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('health endpoint returns ok', function (): void {
    $this->getJson('/api/v1/health')
        ->assertSuccessful()
        ->assertJson(['status' => 'ok']);
});

test('status endpoint includes maintenance window when active', function (): void {
    MaintenanceWindow::query()->create([
        'title' => 'Scheduled maintenance',
        'message' => 'Database upgrade',
        'affects' => ['activation'],
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addHour(),
        'is_active' => true,
        'created_by' => null,
    ]);

    $this->getJson('/api/v1/status')
        ->assertSuccessful()
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('maintenance.title', 'Scheduled maintenance')
        ->assertJsonPath('maintenance.affects.0', 'activation');
});

test('version endpoint returns version metadata', function (): void {
    $this->getJson('/api/v1/version')
        ->assertSuccessful()
        ->assertJsonStructure(['version', 'release']);
});
