<?php

namespace AiChat\Agents;

use AiChat\Contracts\AgentInterface;

class AgentManager
{
    protected array $agents = [];

    protected string $defaultAgent;

    public function __construct(array $agents = [])
    {
        foreach ($agents as $agent) {
            $this->register($agent);
        }
    }

    public function register(AgentInterface $agent): void
    {
        $this->agents[$agent->name()] = $agent;

        if (! isset($this->defaultAgent)) {
            $this->defaultAgent = $agent->name();
        }
    }

    public function get(string $name): ?AgentInterface
    {
        return $this->agents[$name] ?? null;
    }

    public function default(): AgentInterface
    {
        return $this->agents[$this->defaultAgent]
            ?? throw new \RuntimeException('No default agent configured.');
    }

    public function all(): array
    {
        return $this->agents;
    }

    public function resolve(?string $name): AgentInterface
    {
        if ($name !== null && $this->get($name) !== null) {
            return $this->get($name);
        }

        return $this->default();
    }

    public function has(string $name): bool
    {
        return isset($this->agents[$name]);
    }

    public function setDefault(string $name): void
    {
        if (! isset($this->agents[$name])) {
            throw new \RuntimeException("Agent [{$name}] is not registered.");
        }

        $this->defaultAgent = $name;
    }
}
