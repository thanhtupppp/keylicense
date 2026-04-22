<?php

namespace App\Exceptions;

use Exception;

class SeatNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Floating seat not found for this device');
    }
}
