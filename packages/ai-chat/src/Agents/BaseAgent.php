<?php

namespace AiChat\Agents;

use AiChat\Contracts\AgentInterface;

abstract class BaseAgent implements AgentInterface
{
    protected string $name = '';

    protected string $description = '';

    protected string $systemPrompt = '';

    protected array $tools = [];

    protected array $contextProviders = [];

    protected array $policies = [];

    protected bool $readOnly = true;

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function systemPrompt(): string
    {
        return $this->systemPrompt;
    }

    public function tools(): array
    {
        return $this->tools;
    }

    public function contextProviders(): array
    {
        return $this->contextProviders;
    }

    public function policies(): array
    {
        return $this->policies;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function withTools(array $tools): static
    {
        $this->tools = array_merge($this->tools, $tools);

        return $this;
    }

    public function withContextProviders(array $providers): static
    {
        $this->contextProviders = array_merge($this->contextProviders, $providers);

        return $this;
    }

    public function withSystemPrompt(string $prompt): static
    {
        $this->systemPrompt = $prompt;

        return $this;
    }
}
