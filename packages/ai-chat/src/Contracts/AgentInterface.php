<?php

namespace AiChat\Contracts;

interface AgentInterface
{
    public function name(): string;

    public function description(): string;

    public function systemPrompt(): string;

    public function tools(): array;

    public function contextProviders(): array;

    public function policies(): array;

    public function isReadOnly(): bool;
}
