<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitByApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return $next($request);
        }

        // Rate limit key based on API key
        $key = 'api_key:' . $apiKey;

        // Check if rate limit is exceeded (60 requests per 60 seconds)
        if (RateLimiter::tooManyAttempts($key, 60)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                ],
            ], 429)->header('Retry-After', $seconds);
        }

        // Increment the rate limiter
        RateLimiter::hit($key, 60);

        $response = $next($request);

        return $response;
    }
}
