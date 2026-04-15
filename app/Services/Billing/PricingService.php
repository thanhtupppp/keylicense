<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\PlanPricing;

class PricingService
{
    public function resolvePrice(Plan $plan, ?string $currency = null): array
    {
        $currency = strtoupper($currency ?: $plan->currency);

        $pricing = PlanPricing::query()
            ->where('plan_id', $plan->id)
            ->where('currency', $currency)
            ->where(static function ($query): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->orderByDesc('is_default')
            ->orderByDesc('valid_from')
            ->first();

        if (! $pricing) {
            $pricing = PlanPricing::query()
                ->where('plan_id', $plan->id)
                ->where('is_default', true)
                ->where(static function ($query): void {
                    $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                })
                ->orderByDesc('valid_from')
                ->first();
        }

        $resolved = [
            'plan_id' => $plan->id,
            'currency' => $pricing?->currency ?? $plan->currency,
            'price_cents' => $pricing?->price_cents ?? $plan->price_cents,
            'is_default' => (bool) ($pricing?->is_default ?? true),
        ];

        if (blank($resolved['currency'])) {
            $resolved['currency'] = $plan->currency;
        }

        return $resolved;
    }
}
