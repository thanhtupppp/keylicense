<?php

use App\Models\Customer;
use App\Models\NotificationPreference;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Refund;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Middleware is disabled per test suite to keep feature flows focused.
    $this->withoutMiddleware();
});

test('admin can create refund record', function (): void {
    $response = $this->postJson('/api/v1/admin/orders/order-123/refund', [
        'refund_type' => 'full',
        'amount_cents' => 5000,
        'currency' => 'USD',
        'reason' => 'customer_request',
        'auto_revoke' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.refund.refund_type', 'full')
        ->assertJsonPath('data.refund.amount_cents', 5000);

    $this->assertDatabaseCount('refunds', 1);
});

test('customer can update notification preferences', function (): void {
    $customer = Customer::query()->create([
        'email' => 'user@example.com',
        'full_name' => 'Test User',
        'phone' => null,
        'metadata' => [],
    ]);

    $this->patchJson('/api/v1/customer/notification-preferences', [
        'preferences' => [[
            'notification_code' => 'refund_processed',
            'channel' => 'email',
            'enabled' => false,
        ]],
    ], [
        'X-Customer-Id' => $customer->id,
    ])->assertSuccessful();

    $this->assertDatabaseHas('notification_preferences', [
        'customer_id' => $customer->id,
        'notification_code' => 'refund_processed',
        'channel' => 'email',
        'enabled' => false,
    ]);
});

test('customer can register and verify email', function (): void {
    $register = $this->postJson('/api/v1/customer/auth/register', [
        'email' => 'new@example.com',
        'full_name' => 'New User',
        'password' => 'password123',
    ])->assertCreated();

    $token = $register->json('data.verification_token');

    expect($token)->not->toBeEmpty();

    $this->postJson('/api/v1/customer/auth/verify-email', [
        'token' => $token,
    ])->assertSuccessful();

    $this->assertDatabaseHas('customers', [
        'email' => 'new@example.com',
    ]);
});
