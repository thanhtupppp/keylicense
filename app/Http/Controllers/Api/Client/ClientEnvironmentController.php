<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientEnvironmentController extends Controller
{
    public function show(Request $request): JsonResponse
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
            ],
        ]);
    }
}
