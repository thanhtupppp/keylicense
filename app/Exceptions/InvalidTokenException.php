<?php

namespace App\Exceptions;

use Exception;

class InvalidTokenException extends Exception
{
    public function __construct(string $reason = 'Invalid token')
    {
        parent::__construct($reason);
    }
}
