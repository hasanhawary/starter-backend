<?php

namespace App\Exceptions;

use Exception;

class InactiveUserException extends Exception
{
    public function __construct(string $message = '', int $code = 401)
    {
        parent::__construct( $message, $code);
    }
}
