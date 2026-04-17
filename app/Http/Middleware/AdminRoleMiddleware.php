<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $admin = $request->attributes->get('admin_user') ?? $request->user('admin') ?? $request->user();

        if (! $admin instanceof AdminUser || ! $admin->is_active) {
            return ApiResponse::error('UNAUTHORIZED', 'Admin authentication required.', 401);
        }

        if ($roles !== [] && ! in_array($admin->role, $roles, true)) {
            return ApiResponse::error('FORBIDDEN', 'Insufficient admin role.', 403);
        }

        return $next($request);
    }
}
