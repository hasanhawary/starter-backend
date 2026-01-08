<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class InActiveUserException extends Exception
{
    public function __construct(string $message = '', int $code = 401)
    {
        parent::__construct(
            $message ?? __('api.account_not_active'),
            $code ?? ResponseAlias::HTTP_FORBIDDEN
        );
    }
}
