<?php

namespace App\Services\Reporting;

use App\Models\Activation;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Subscription;

class AnalyticsService
{
    public function dashboard(): array
    {
        return [
            'active_licenses' => LicenseKey::query()->where('status', 'active')->count(),
            'active_activations' => Activation::query()->where('status', 'active')->count(),
            'churned_subscriptions' => Subscription::query()->where('status', 'cancelled')->count(),
            'expiring_entitlements' => Entitlement::query()->whereBetween('expires_at', [now(), now()->addDays(30)])->count(),
        ];
    }
}
