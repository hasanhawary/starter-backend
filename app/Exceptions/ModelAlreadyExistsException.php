<?php

namespace App\Exceptions;

use Exception;

class ModelAlreadyExistsException extends Exception
{
    public function __construct(protected array $data, string $message = '', int $code = 433)
    {
        parent::__construct($message, $code);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
