<?php

namespace AiChat\Generator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AgentGenerator
{
    public function __construct(
        private readonly StubManager $stubManager,
    ) {}

    public function generate(string $name, array $options = []): string
    {
        $outputPath = app_path('AI/Agents');
        File::ensureDirectoryExists($outputPath);

        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name))).'Agent';

        $filePath = $outputPath."/{$className}.php";

        $stub = $this->stubManager->get('agent');

        $description = $options['description'] ?? "AI agent for {$name} operations";
        $systemPrompt = $options['system_prompt'] ?? $this->buildDefaultSystemPrompt($name);
        $readOnly = isset($options['read_only']) ? ($options['read_only'] ? 'true' : 'false') : 'true';
        $tools = $this->buildToolsArray($options['tools'] ?? []);
        $policies = $this->buildPoliciesArray($options['policies'] ?? []);
        $contextProviders = $this->buildContextProvidersArray($options['context_providers'] ?? []);

        $content = $this->stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Agents',
            'class' => $className,
            'name' => Str::snake($name),
            'description' => addslashes($description),
            'system_prompt' => addslashes($systemPrompt),
            'read_only' => $readOnly,
            'tools' => $tools,
            'policies' => $policies,
            'context_providers' => $contextProviders,
        ]);

        File::put($filePath, $content);

        return $filePath;
    }

    protected function buildDefaultSystemPrompt(string $name): string
    {
        return "You are an AI assistant specialized in {$name} operations. Answer questions accurately and concisely based on available data. Never modify any data as you operate in read-only mode.";
    }

    protected function buildToolsArray(array $tools): string
    {
        if (empty($tools)) {
            return '[]';
        }

        $items = array_map(fn (string $tool) => "            {$tool}::class,", $tools);

        return "[\n".implode("\n", $items)."\n        ]";
    }

    protected function buildPoliciesArray(array $policies): string
    {
        if (empty($policies)) {
            return "[\n            \\AiChat\\AiChat\\Policies\\DefaultReadOnlyPolicy::class,\n        ]";
        }

        $items = array_map(fn (string $policy) => "            {$policy}::class,", $policies);

        return "[\n".implode("\n", $items)."\n        ]";
    }

    protected function buildContextProvidersArray(array $providers): string
    {
        if (empty($providers)) {
            return '[]';
        }

        $items = array_map(fn (string $provider) => "            {$provider}::class,", $providers);

        return "[\n".implode("\n", $items)."\n        ]";
    }
}
