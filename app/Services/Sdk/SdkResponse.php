<?php

namespace App\Services\Sdk;

final class SdkResponse
{
    private function __construct(
        public readonly bool $ok,
        public readonly int $status,
        public readonly string $code,
        public readonly string $message,
        public readonly array $data,
    ) {
    }

    public static function success(array $data, int $status = 200): self
    {
        return new self(true, $status, 'OK', 'OK', $data);
    }

    public static function failure(int $status, string $code, string $message): self
    {
        return new self(false, $status, $code, $message, []);
    }
}
