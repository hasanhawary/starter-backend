<?php

namespace App\Exceptions;

use Exception;

class InvalidEmailAndPasswordCombinationException extends Exception
{
    public function __construct(string $message = '', int $code = 401)
    {
        parent::__construct( $message, $code);
    }
}
