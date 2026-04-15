<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key');

        if (! $key) {
            return ApiResponse::error('UNAUTHORIZED', 'Missing X-API-Key header.', 401);
        }

        $apiKey = ApiKey::query()
            ->where('api_key', hash('sha256', $key))
            ->where('is_active', true)
            ->first();

        if (! $apiKey) {
            return ApiResponse::error('UNAUTHORIZED', 'Invalid API key.', 401);
        }

        return $next($request);
    }
}
