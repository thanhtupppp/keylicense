<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows customer verification state', function (): void {
    $customer = Customer::query()->create([
        'email' => 'verify@example.com',
        'full_name' => 'Verify Customer',
        'phone' => null,
        'metadata' => [],
        'verification_token' => 'token-123',
        'verification_expires_at' => now()->addHour(),
    ]);

    $this->withHeader('X-Customer-Id', $customer->id)
        ->getJson('/api/v1/customer/verification')
        ->assertSuccessful()
        ->assertJsonPath('data.customer.email', 'verify@example.com')
        ->assertJsonPath('data.customer.verification_pending', true);
});

it('verifies a customer email', function (): void {
    $customer = Customer::query()->create([
        'email' => 'verify@example.com',
        'full_name' => 'Verify Customer',
        'phone' => null,
        'metadata' => [],
        'verification_token' => 'token-123',
        'verification_expires_at' => now()->addHour(),
    ]);

    $this->postJson('/api/v1/customer/verification/verify', [
        'email' => 'verify@example.com',
        'verification_token' => 'token-123',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.verified', true);

    expect(Customer::query()->find($customer->id)?->email_verified_at)->not->toBeNull();
});

it('resends verification token', function (): void {
    $customer = Customer::query()->create([
        'email' => 'verify@example.com',
        'full_name' => 'Verify Customer',
        'phone' => null,
        'metadata' => [],
    ]);

    $this->postJson('/api/v1/customer/verification/resend', [
        'email' => 'verify@example.com',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.resent', true);

    expect(Customer::query()->find($customer->id)?->verification_token)->not->toBeNull();
});
