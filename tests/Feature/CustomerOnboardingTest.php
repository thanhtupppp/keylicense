<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows customer onboarding state', function (): void {
    $customer = Customer::query()->create([
        'email' => 'onboard@example.com',
        'full_name' => 'Onboard Customer',
        'phone' => null,
        'metadata' => [
            'onboarding' => [
                'step' => 'verify_email',
                'completed' => false,
            ],
        ],
    ]);

    $this->withHeader('X-Customer-Id', $customer->id)
        ->getJson('/api/v1/customer/onboarding')
        ->assertSuccessful()
        ->assertJsonPath('data.customer_id', $customer->id)
        ->assertJsonPath('data.onboarding.step', 'verify_email');
});

it('skips customer onboarding', function (): void {
    $customer = Customer::query()->create([
        'email' => 'onboard@example.com',
        'full_name' => 'Onboard Customer',
        'phone' => null,
        'metadata' => [],
    ]);

    $this->withHeader('X-Customer-Id', $customer->id)
        ->postJson('/api/v1/customer/onboarding/skip')
        ->assertSuccessful()
        ->assertJsonPath('data.skipped', true);

    expect(Customer::query()->find($customer->id)?->metadata['onboarding']['completed'])->toBeTrue();
});
