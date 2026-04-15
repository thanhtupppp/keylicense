<?php

use App\Models\DunningConfig;
use App\Models\DunningLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use Tests\Feature\Concerns\DunningFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Middleware is disabled per test suite to keep controller tests isolated.
    $this->withoutMiddleware();
});

test('payment failed webhook schedules dunning steps', function (): void {
    $subscription = DunningFixtures::seedPastDueSubscription();
    DunningFixtures::seedDunningConfig(1);
    DunningFixtures::seedDunningConfig(3);

    $this->postJson('/api/v1/admin/billing-webhooks/payment-failed', [
        'subscription_id' => $subscription->id,
    ])->assertSuccessful()
      ->assertJsonPath('data.status', 'past_due');

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => 'past_due',
    ]);
});

test('recovers subscription on payment success webhook', function (): void {
    $subscription = DunningFixtures::seedPastDueSubscription();

    postJson('/api/v1/admin/billing-webhooks/payment-succeeded', [
        'subscription_id' => $subscription->id,
    ])->assertSuccessful();

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => 'active',
        'cancel_at_period_end' => false,
    ]);

    $this->assertDatabaseHas('license_keys', [
        'entitlement_id' => $subscription->entitlement_id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('dunning_logs', [
        'subscription_id' => $subscription->id,
        'action' => 'payment_recovered',
        'result' => 'recovered',
    ]);
});

test('returns dunning report grouped by product and subscription', function (): void {
    $subscription = DunningFixtures::seedPastDueSubscription();

    DunningLog::query()->create([
        'subscription_id' => $subscription->id,
        'step' => 1,
        'action' => DunningConfig::ACTION_EMAIL,
        'executed_at' => now(),
        'result' => 'recovered',
        'notes' => null,
    ]);

    $this->getJson('/api/v1/admin/reports/dunning')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'report' => [
                    'from',
                    'to',
                    'total_actions',
                    'recovered_count',
                    'cancelled_count',
                    'suspended_count',
                    'recovery_rate_percent',
                ],
                'by_product',
                'by_subscription',
            ],
        ]);
});
