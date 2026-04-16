<?php

use App\Jobs\RunDunningStepJob;
use App\Models\DunningConfig;
use App\Services\Billing\DunningOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Concerns\DunningFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('creates a versioned schedule manifest and dispatches jobs from it', function (): void {
    Bus::fake();
    $subscription = DunningFixtures::createPastDueSubscription();
    DunningFixtures::createDunningConfig(1);
    DunningFixtures::createDunningConfig(3);

    app(DunningOrchestrator::class)->handlePaymentFailed($subscription);

    $manifest = Cache::get('dunning:schedule:'.strtolower($subscription->id));

    expect($manifest)->toBeArray();
    expect($manifest['version'])->toBe(1);
    expect($manifest['state'])->toBe('scheduled');
    expect($manifest['steps'])->toHaveCount(2);

    Bus::assertDispatched(RunDunningStepJob::class, 2);
});

it('invalidates schedule on success and marks manifest cancelled', function (): void {
    $subscription = DunningFixtures::createPastDueSubscription();
    $orchestrator = app(DunningOrchestrator::class);

    $orchestrator->handlePaymentFailed($subscription);
    $orchestrator->handlePaymentSucceeded($subscription);

    $manifest = Cache::get('dunning:schedule:'.strtolower($subscription->id));

    expect($manifest)->toBeArray();
    expect($manifest['state'])->toBe('invalidated');
    expect($orchestrator->isScheduleCurrent($subscription->id, 1))->toBeFalse();
});

it('rejects stale dunning jobs after a new version is scheduled', function (): void {
    $subscription = DunningFixtures::createPastDueSubscription();
    $orchestrator = app(DunningOrchestrator::class);

    $orchestrator->handlePaymentFailed($subscription);
    $orchestrator->cancelPending($subscription->id);

    Cache::put('dunning:schedule:'.strtolower($subscription->id), [
        'subscription_id' => $subscription->id,
        'product_id' => $subscription->entitlement?->plan?->product_id,
        'version' => 2,
        'state' => 'scheduled',
        'steps' => [],
        'created_at' => now()->toISOString(),
    ], now()->addDay());

    expect($orchestrator->isScheduleCurrent($subscription->id, 1))->toBeFalse();
    expect($orchestrator->isScheduleCurrent($subscription->id, 2))->toBeTrue();
});
