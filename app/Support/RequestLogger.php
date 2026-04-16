<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLogger
{
    public static function warning(string $message, Request $request, array $context = []): void
    {
        Log::warning($message, array_merge(self::context($request), $context));
    }

    public static function error(string $message, Request $request, array $context = []): void
    {
        Log::error($message, array_merge(self::context($request), $context));
    }

    private static function context(Request $request): array
    {
        return [
            'request_id' => $request->header('X-Request-Id'),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
