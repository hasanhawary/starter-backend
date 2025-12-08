<?php

namespace App\Exceptions;

use Exception;

class AccountNotFoundException extends Exception
{
    public function __construct(string $message = '', int $code = 403)
    {
        parent::__construct( $message, $code);
    }
}
