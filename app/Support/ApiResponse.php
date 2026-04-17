<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ApiResponse
{
    public static function success(mixed $data = [], int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => Arr::get($meta, 'message', 'OK'),
            'data' => self::normalize($data),
            'error' => null,
            'meta' => self::meta($meta),
        ], $status);
    }

    public static function error(string $code, string $message, int $status = 400, array $details = [], array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
            ],
            'meta' => self::meta($meta),
        ], $status);
    }

    private static function normalize(mixed $data): array
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if (is_object($data)) {
            $data = (array) $data;
        }

        if (! is_array($data)) {
            $data = [$data];
        }

        return $data;
    }

    private static function meta(array $meta = []): array
    {
        return array_merge([
            'request_id' => (string) Str::uuid(),
            'timestamp' => now()->toISOString(),
        ], $meta);
    }
}
