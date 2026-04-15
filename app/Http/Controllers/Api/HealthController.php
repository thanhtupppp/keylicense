<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function health(): JsonResponse
    {
        return ApiResponse::success(['status' => 'ok']);
    }

    public function status(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'operational',
            'components' => [
                'api' => 'operational',
                'database' => 'operational',
                'cache' => 'operational',
                'email' => 'operational',
            ],
            'maintenance' => null,
        ]);
    }

    public function version(): JsonResponse
    {
        return ApiResponse::success([
            'version' => config('app.version', 'unknown'),
        ]);
    }
}
