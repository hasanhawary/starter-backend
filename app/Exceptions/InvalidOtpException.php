<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class InvalidOtpException extends Exception
{
    public function __construct(string $message = '', ?int $code = null)
    {
        parent::__construct($message, $code ?? ResponseAlias::HTTP_NOT_ACCEPTABLE);
    }
}
