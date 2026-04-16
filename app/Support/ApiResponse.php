<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ApiResponse
{
    public static function success(mixed $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => self::normalize($data),
            'error' => null,
            'meta' => self::meta(),
        ], $status);
    }

    public static function error(string $code, string $message, int $status = 400, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
            ],
            'meta' => self::meta(),
        ], $status);
    }

    private static function normalize(mixed $data): array|object
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if (is_array($data) || is_object($data)) {
            return $data;
        }

        return [$data];
    }

    private static function meta(): array
    {
        return [
            'request_id' => (string) Str::uuid(),
            'timestamp' => now()->toISOString(),
        ];
    }
}
