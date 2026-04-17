<?php

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Subscription;
use App\Services\Billing\CouponService;
use App\Services\Billing\RenewalService;
use App\Services\Billing\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\ClientLicenseFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createSubscriptionForBillingTests(string $status = 'active'): Subscription
{
    $license = ClientLicenseFixtures::createLicense($status);
    $entitlement = $license->entitlement;

    return Subscription::query()->create([
        'id' => (string) Str::uuid(),
        'entitlement_id' => $entitlement->id,
        'source' => 'stripe',
        'status' => $status,
        'current_period_start' => now()->subMonth(),
        'current_period_end' => now()->addMonth(),
        'cancel_at_period_end' => false,
        'metadata' => [],
    ]);
}

test('renewal service extends subscription and entitlement', function (): void {
    $subscription = createSubscriptionForBillingTests();

    $renewed = app(RenewalService::class)->renew($subscription, now()->addMonths(2));

    expect($renewed->status)->toBe('active');
    expect($renewed->entitlement->refresh()->expires_at)->not->toBeNull();
});

test('refund service revokes entitlement licenses', function (): void {
    $subscription = createSubscriptionForBillingTests();

    app(RefundService::class)->revokeForSubscription($subscription);

    expect($subscription->fresh()->status)->toBe('cancelled');
});

test('coupon service applies and records usage', function (): void {
    $subscription = createSubscriptionForBillingTests();

    Coupon::query()->create([
        'id' => (string) Str::uuid(),
        'code' => 'SAVE10',
        'name' => 'Save 10',
        'discount_type' => 'fixed',
        'discount_value' => 1000,
        'currency' => 'USD',
        'max_redemptions' => 1,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'is_active' => true,
    ]);

    $result = app(CouponService::class)->apply('SAVE10', $subscription);

    expect($result['valid'])->toBeTrue();
    expect($result['discount_amount'])->toBe(1000);
    expect(CouponUsage::query()->count())->toBe(1);
});
