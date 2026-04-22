<?php

namespace App\Exceptions;

use Exception;

class LicenseExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('Cannot restore license: expiry date has passed');
    }
}
