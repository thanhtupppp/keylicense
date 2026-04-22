<?php

namespace App\Http\Middleware;

use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        // Check if API key is provided
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'API key is required',
                ],
            ], 401);
        }

        // Find product by API key (not soft deleted)
        $product = Product::where('api_key', $apiKey)->first();

        // Check if product exists and is not soft deleted
        if (!$product) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Invalid API key',
                ],
            ], 401);
        }

        // Inject product into request attributes
        $request->attributes->set('product', $product);

        return $next($request);
    }
}
