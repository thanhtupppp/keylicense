<?php

namespace App\Services\Billing;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Subscription;

class CouponService
{
    public function validate(string $code): array
    {
        /** @var Coupon|null $coupon */
        $coupon = Coupon::query()
            ->where('code', '=', $code)
            ->where('is_active', '=', true)
            ->first(['*']);

        if (! $coupon) {
            return ['valid' => false, 'message' => 'Coupon not found.'];
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return ['valid' => false, 'message' => 'Coupon is not active yet.'];
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return ['valid' => false, 'message' => 'Coupon has expired.'];
        }

        $usageCount = CouponUsage::query()->where('coupon_id', $coupon->id)->count();
        if ($coupon->max_redemptions !== null && $usageCount >= $coupon->max_redemptions) {
            return ['valid' => false, 'message' => 'Coupon redemption limit reached.'];
        }

        return [
            'valid' => true,
            'coupon_code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'currency' => $coupon->currency,
        ];
    }

    public function apply(string $code, Subscription $subscription): array
    {
        $result = $this->validate($code);

        if (($result['valid'] ?? false) !== true) {
            return $result;
        }

        $coupon = Coupon::query()->where('code', '=', $code)->firstOrFail(['*']);
        $license = $subscription->entitlement?->licenses()->first();
        $discountAmount = (int) $coupon->discount_value;

        CouponUsage::query()->create([
            'coupon_id' => $coupon->id,
            'subscription_id' => $subscription->id,
            'license_id' => $license?->id,
            'discount_amount' => $discountAmount,
            'redeemed_at' => now(),
        ]);

        return [
            'valid' => true,
            'coupon_code' => $coupon->code,
            'discount_amount' => $discountAmount,
            'discount_type' => $coupon->discount_type,
        ];
    }
}
