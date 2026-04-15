<?php

namespace App\Http\Middleware;

use App\Models\AdminToken;
use App\Models\AdminUser;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return ApiResponse::error('UNAUTHORIZED', 'Missing bearer token.', 401);
        }

        $tokenHash = hash('sha256', $bearer);

        $adminToken = AdminToken::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $adminToken instanceof AdminToken) {
            return ApiResponse::error('UNAUTHORIZED', 'Invalid bearer token.', 401);
        }

        $admin = AdminUser::query()
            ->whereKey($adminToken->admin_user_id)
            ->where('is_active', true)
            ->first();

        if (! $admin) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin account is inactive.', 401);
        }

        $adminToken->forceFill([
            'last_activity_at' => now(),
            'last_ip' => $request->ip(),
            'last_user_agent' => $request->userAgent(),
        ])->save();

        $request->attributes->set('admin_user', $admin);
        $request->attributes->set('admin_token', $adminToken);

        return $next($request);
    }
}
