<?php

namespace AiChat\MCP;

use RuntimeException;

class ToolValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors = [])
    {
        parent::__construct('Tool input validation failed: '.implode(', ', $errors));
    }
}
