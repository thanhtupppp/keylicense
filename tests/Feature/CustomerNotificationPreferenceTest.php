<?php

use App\Models\Customer;
use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows customer notification preferences', function (): void {
    $customer = Customer::query()->create([
        'email' => 'prefs@example.com',
        'full_name' => 'Prefs Customer',
        'phone' => null,
        'metadata' => [],
    ]);

    NotificationPreference::query()->create([
        'customer_id' => $customer->id,
        'notification_code' => 'license_expiring_soon',
        'channel' => 'email',
        'enabled' => true,
        'unsubscribe_token' => 'token-abc',
    ]);

    $this->withHeader('X-Customer-Id', $customer->id)
        ->getJson('/api/v1/customer/notification-preferences')
        ->assertSuccessful()
        ->assertJsonPath('data.preferences.0.notification_code', 'license_expiring_soon');
});

it('updates customer notification preferences and preserves unsubscribe token', function (): void {
    $customer = Customer::query()->create([
        'email' => 'prefs@example.com',
        'full_name' => 'Prefs Customer',
        'phone' => null,
        'metadata' => [],
    ]);

    NotificationPreference::query()->create([
        'customer_id' => $customer->id,
        'notification_code' => 'license_expiring_soon',
        'channel' => 'email',
        'enabled' => false,
        'unsubscribe_token' => 'token-abc',
    ]);

    $this->withHeader('X-Customer-Id', $customer->id)
        ->patchJson('/api/v1/customer/notification-preferences', [
            'customer_id' => $customer->id,
            'preferences' => [
                [
                    'notification_code' => 'license_expiring_soon',
                    'channel' => 'email',
                    'enabled' => true,
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.updated', true);

    expect(NotificationPreference::query()
        ->where('customer_id', $customer->id)
        ->where('notification_code', 'license_expiring_soon')
        ->where('channel', 'email')
        ->value('unsubscribe_token'))
        ->toBe('token-abc');
});

it('unsubscribes a notification preference by token', function (): void {
    $customer = Customer::query()->create([
        'email' => 'prefs@example.com',
        'full_name' => 'Prefs Customer',
        'phone' => null,
        'metadata' => [],
    ]);

    $preference = NotificationPreference::query()->create([
        'customer_id' => $customer->id,
        'notification_code' => 'license_expiring_soon',
        'channel' => 'email',
        'enabled' => true,
        'unsubscribe_token' => 'token-abc',
    ]);

    $this->postJson('/api/v1/customer/notification-preferences/unsubscribe', [
        'unsubscribe_token' => $preference->unsubscribe_token,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.updated', true);

    expect(NotificationPreference::query()->find($preference->id)?->enabled)->toBeFalse();
});
