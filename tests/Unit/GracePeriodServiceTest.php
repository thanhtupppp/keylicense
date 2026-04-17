<?php

use App\Models\Activation;
use App\Services\Licensing\GracePeriodService;
use App\Support\Licensing\LicenseActivationStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\ClientLicenseFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('grace period sweep expires stale activations', function (): void {
    $license = ClientLicenseFixtures::createLicense();

    $activation = Activation::query()->create([
        'id' => (string) Str::uuid(),
        'activation_code' => 'activation-1',
        'license_id' => $license->id,
        'product_code' => 'prod-1',
        'domain' => 'example.com',
        'environment' => 'production',
        'status' => LicenseActivationStates::ACTIVE,
        'activated_at' => now()->subDays(10),
        'last_validated_at' => now()->subDays(10),
    ]);

    $count = app(GracePeriodService::class)->sweep();

    expect($count)->toBe(1);
    expect($activation->fresh()->status)->toBe(LicenseActivationStates::EXPIRED);
});
