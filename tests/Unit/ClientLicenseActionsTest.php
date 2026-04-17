<?php

use App\Actions\Licensing\ActivateLicenseAction;
use App\Actions\Licensing\ConfirmOfflineChallengeAction;
use App\Actions\Licensing\DeactivateLicenseAction;
use App\Actions\Licensing\RequestOfflineChallengeAction;
use App\Actions\Licensing\ValidateLicenseAction;
use App\Models\Activation;
use App\Services\Licensing\OfflineChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ClientLicenseFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    ClientLicenseFixtures::seedApiKey();
});

test('activate license action returns activation result', function (): void {
    $license = ClientLicenseFixtures::createLicense();

    $result = app(ActivateLicenseAction::class)->execute([
        'license_key' => 'TEST-LICENSE-KEY',
        'product_code' => 'prod-1',
        'domain' => 'example.com',
        'environment' => 'production',
    ]);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('active');
    expect($result->activationId)->not->toBeEmpty();
    expect($result->payload['license_id'])->toBe($license->id);
});

test('validate license action returns valid result', function (): void {
    $license = ClientLicenseFixtures::createLicense();
    $activation = Activation::query()->create([
        'id' => (string) str()->uuid(),
        'activation_code' => 'activation-1',
        'license_id' => $license->id,
        'product_code' => 'prod-1',
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => 'active',
        'activated_at' => now(),
        'last_validated_at' => now(),
    ]);

    $result = app(ValidateLicenseAction::class)->execute([
        'license_key' => 'TEST-LICENSE-KEY',
        'product_code' => 'prod-1',
        'activation_id' => $activation->activation_code,
        'domain' => 'example.com',
        'environment' => 'production',
    ]);

    expect($result->valid)->toBeTrue();
    expect($result->status)->toBe('valid');
    expect($result->payload['activation_id'])->toBe($activation->activation_code);
});

test('deactivate license action marks activation deactivated', function (): void {
    $license = ClientLicenseFixtures::createLicense();
    $activation = Activation::query()->create([
        'id' => (string) str()->uuid(),
        'activation_code' => 'activation-1',
        'license_id' => $license->id,
        'product_code' => 'prod-1',
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => 'active',
        'activated_at' => now(),
        'last_validated_at' => now(),
    ]);

    $result = app(DeactivateLicenseAction::class)->execute([
        'license_key' => 'TEST-LICENSE-KEY',
        'activation_id' => $activation->activation_code,
    ]);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('deactivated');
    expect($result->activationId)->toBe($activation->activation_code);
    expect($activation->fresh()->status)->toBe('deactivated');
});

test('offline challenge actions request and confirm challenge', function (): void {
    ClientLicenseFixtures::createLicense();

    $requestResult = app(RequestOfflineChallengeAction::class)->execute([
        'license_key' => 'TEST-LICENSE-KEY',
        'product_code' => 'prod-1',
        'domain' => 'example.com',
        'environment' => 'production',
    ]);

    expect($requestResult->issued)->toBeTrue();
    expect($requestResult->challengeId)->not->toBeEmpty();
    expect($requestResult->payload['status'])->toBe('challenge_issued');

    $confirmResult = app(ConfirmOfflineChallengeAction::class)->execute([
        'license_key' => 'TEST-LICENSE-KEY',
        'activation_id' => $requestResult->challengeId,
        'challenge' => $requestResult->payload['challenge'],
    ]);

    expect($confirmResult->issued)->toBeTrue();
    expect($confirmResult->payload['status'])->toBe('challenge_confirmed');
});

test('offline challenge service returns not found for missing license', function (): void {
    $result = app(OfflineChallengeService::class)->request([
        'license_key' => 'MISSING',
        'product_code' => 'prod-1',
    ]);

    expect($result->issued)->toBeFalse();
    expect($result->payload['message'])->toBe('License key not found.');
});
