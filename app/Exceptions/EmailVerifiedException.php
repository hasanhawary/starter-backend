<?php

namespace App\Exceptions;

use Exception;

class EmailVerifiedException extends Exception
{
    public function __construct(string $message = '', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
