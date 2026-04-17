<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Support\ApiResponse;
use App\Services\Billing\PlatformConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientEnvironmentController extends Controller
{
    public function show(Request $request, PlatformConfigService $platformConfig): JsonResponse
    {
        $slug = $request->query('environment', 'production');
        $environment = Environment::query()->where('slug', $slug)->first();

        if (! $environment) {
            return ApiResponse::error('not_found', 'Environment not found.', 404);
        }

        return ApiResponse::success([
            'status' => 'ok',
            'environment' => [
                'slug' => $environment->slug,
                'is_production' => $environment->is_production,
                'rate_limit_multiplier' => (float) $environment->rate_limit_multiplier,
                'heartbeat_interval_hours' => $environment->heartbeat_interval_hours ?? $platformConfig->environmentHeartbeatHours($environment->slug),
                'grace_period_days' => $environment->grace_period_days ?? $platformConfig->environmentGracePeriodDays($environment->slug),
            ],
        ]);
    }
}
