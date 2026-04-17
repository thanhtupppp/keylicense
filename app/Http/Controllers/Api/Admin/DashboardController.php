<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $period = $request->integer('days', 30);
        $since = now()->subDays(max(1, $period));

        return ApiResponse::success([
            'summary' => [
                'products' => Product::query()->count(),
                'entitlements' => Entitlement::query()->count(),
                'licenses' => LicenseKey::query()->count(),
                'activations' => Activation::query()->count(),
            ],
            'activity' => [
                'licenses_issued' => LicenseKey::query()->where('created_at', '>=', $since)->count(),
                'activations_created' => Activation::query()->where('created_at', '>=', $since)->count(),
                'licenses_revoked' => LicenseKey::query()->where('status', 'revoked')->count(),
                'licenses_suspended' => LicenseKey::query()->where('status', 'suspended')->count(),
                'active_entitlements' => Entitlement::query()->where('status', 'active')->count(),
            ],
            'filters' => [
                'days' => $period,
            ],
        ]);
    }
}
