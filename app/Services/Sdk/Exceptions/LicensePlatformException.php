<?php

namespace App\Services\Sdk\Exceptions;

use RuntimeException;

class LicensePlatformException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $codeName = null,
        public readonly int $statusCode = 0,
        public readonly array $context = []
    ) {
        parent::__construct($message, $statusCode);
    }
}
