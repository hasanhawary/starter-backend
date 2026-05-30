<?php

namespace AiChat\Contracts;

interface ContextProviderInterface
{
    public function name(): string;

    public function provide(): array;
}
