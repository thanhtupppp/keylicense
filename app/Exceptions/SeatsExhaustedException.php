<?php

namespace App\Exceptions;

use Exception;

class SeatsExhaustedException extends Exception
{
    public function __construct(int $maxSeats)
    {
        parent::__construct(
            "All {$maxSeats} seats are currently in use for this floating license"
        );
    }
}
