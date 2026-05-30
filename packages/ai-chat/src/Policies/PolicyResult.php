<?php

namespace AiChat\Policies;

class PolicyResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $reason = '',
        public readonly ?string $policy = null,
    ) {}

    public static function allowed(string $reason = '', ?string $policy = null): self
    {
        return new self(true, $reason, $policy);
    }

    public static function denied(string $reason, ?string $policy = null): self
    {
        return new self(false, $reason, $policy);
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function isDenied(): bool
    {
        return ! $this->allowed;
    }
}
