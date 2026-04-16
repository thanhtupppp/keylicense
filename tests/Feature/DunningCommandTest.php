<?php

use App\Jobs\RunDunningStepJob;
use App\Models\DunningConfig;
use App\Services\Billing\DunningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Feature\Concerns\DunningFixtures;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('dispatches configured dunning steps for all products', function (): void {
    DunningFixtures::seedDunningConfig(1);
    DunningFixtures::seedDunningConfig(3);

    Bus::fake();

    $this->artisan('dunning:run-daily --all --sync')
        ->assertExitCode(0);

    Bus::assertDispatched(RunDunningStepJob::class, 2);
});

test('dispatches only product specific and global dunning steps when product is provided', function (): void {
    $product = DunningFixtures::createPastDueSubscription()->entitlement->plan->product;

    DunningFixtures::seedDunningConfig(1);
    DunningFixtures::seedDunningConfig(3);
    DunningFixtures::seedDunningConfig(7, DunningConfig::ACTION_EMAIL, $product->id);

    Bus::fake();

    $this->artisan("dunning:run-daily --product_id={$product->id} --sync")
        ->assertExitCode(0);

    Bus::assertDispatched(RunDunningStepJob::class, 3);
});

test('runs dunning step and creates logs', function (): void {
    $subscription = DunningFixtures::createPastDueSubscription();
    DunningFixtures::seedDunningConfig(1);

    app(DunningService::class)->runStep(1);

    $this->assertDatabaseHas('dunning_logs', [
        'subscription_id' => $subscription->id,
        'step' => 1,
        'action' => DunningConfig::ACTION_EMAIL,
        'result' => 'sent',
    ]);
});

test('recovery service restores subscription entitlement and licenses', function (): void {
    $subscription = DunningFixtures::createPastDueSubscription();
    $service = app(DunningService::class);

    $service->recoverSubscription($subscription);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => 'active',
        'cancel_at_period_end' => false,
    ]);

    $this->assertDatabaseHas('license_keys', [
        'entitlement_id' => $subscription->entitlement_id,
        'status' => 'active',
    ]);
});
