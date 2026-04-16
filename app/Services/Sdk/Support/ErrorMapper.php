<?php

namespace App\Services\Sdk\Support;

use App\Services\Sdk\Exceptions\LicensePlatformException;

class ErrorMapper
{
    public function map(int $status, ?string $code, ?string $message, array $context = []): LicensePlatformException
    {
        return new LicensePlatformException(
            message: $message ?: 'License platform request failed.',
            codeName: $code,
            statusCode: $status,
            context: $context,
        );
    }

    public function throw(int $status, ?string $code, ?string $message, array $context = []): never
    {
        throw $this->map($status, $code, $message, $context);
    }
}
