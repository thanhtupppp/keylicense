<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminLoginService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, AdminLoginService $adminLoginService): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $deviceKey = hash('sha256', (string) $request->userAgent().'|'.(string) $request->ip());

        $result = $adminLoginService->login(
            email: $payload['email'],
            password: $payload['password'],
            remember: false,
            deviceKey: $deviceKey,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (isset($result['error'])) {
            return ApiResponse::error(
                $result['error']['code'],
                $result['error']['message'],
                $result['error']['status']
            );
        }

        return ApiResponse::success($result);
    }
}
