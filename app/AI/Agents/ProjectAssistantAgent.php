<?php

namespace App\AI\Agents;

use AiChat\Agents\BaseAgent;
use AiChat\Policies\DefaultReadOnlyPolicy;

class ProjectAssistantAgent extends BaseAgent
{
    protected string $name = 'project_assistant';

    protected string $description = 'AI assistant that answers questions about project data safely';

    protected string $systemPrompt = 'You are a read-only AI assistant designed to answer questions about project data safely and accurately. Never create, update, delete, or modify any data.';

    protected array $tools = [];

    protected array $contextProviders = [];

    protected array $policies = [
        DefaultReadOnlyPolicy::class,
    ];

    protected bool $readOnly = true;
}