<?php

namespace App\Exceptions;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(string $currentState, string $action)
    {
        parent::__construct(
            "Cannot perform action '{$action}' on license in state '{$currentState}'"
        );
    }
}
