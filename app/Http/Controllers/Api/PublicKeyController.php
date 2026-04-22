<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class PublicKeyController extends Controller
{
    /**
     * Get the public key for JWT verification.
     *
     * GET /api/v1/public-key
     */
    public function show(): JsonResponse
    {
        $publicKeyPath = config('jwt.public_key_path');

        if (!$publicKeyPath || !file_exists($publicKeyPath)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'PUBLIC_KEY_NOT_FOUND',
                    'message' => 'Public key not found',
                ],
            ], 500);
        }

        $publicKey = File::get($publicKeyPath);

        return response()->json([
            'success' => true,
            'data' => [
                'public_key' => $publicKey,
                'algorithm' => 'RS256',
            ],
            'error' => null,
        ], 200);
    }
}
