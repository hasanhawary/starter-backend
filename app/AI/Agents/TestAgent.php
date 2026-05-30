<?php

namespace App\AI\Agents;

use AiChat\Agents\BaseAgent;

class {{CLASS_NAME}} extends BaseAgent
{
    protected string $name = '{{AGENT_NAME}}';

    protected string $description = '{{AGENT_DESCRIPTION}}';

    protected string $systemPrompt = 'You are an AI assistant specialized in Test operations. Answer questions accurately and concisely based on available data. Never modify any data as you operate in read-only mode.';

    protected array $tools = [[]];

    protected array $contextProviders = [[]];

    protected array $policies = [[
            \AiChat\AiChat\Policies\DefaultReadOnlyPolicy::class,
        ]];

    protected bool $readOnly = true;
}
