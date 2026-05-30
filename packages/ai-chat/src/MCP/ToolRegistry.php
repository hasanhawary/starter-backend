<?php

namespace AiChat\MCP;

use AiChat\Contracts\ToolInterface;

class ToolRegistry
{
    private array $tools = [];

    public function __construct(array $tools = [])
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function unregister(string $name): void
    {
        unset($this->tools[$name]);
    }

    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    public function all(): array
    {
        return $this->tools;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function names(): array
    {
        return array_keys($this->tools);
    }

    public function schemas(): array
    {
        return array_map(fn (ToolInterface $tool) => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'inputSchema' => $tool->schema(),
        ], array_values($this->tools));
    }
}
