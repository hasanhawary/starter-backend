<?php

namespace App\AI\Tools\Custom;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
{{MODEL_IMPORT}}

class {{CLASS_NAME}} implements ToolInterface
{
    public function name(): string
    {
        return '{{TOOL_NAME}}';
    }

    public function description(): string
    {
        return '{{TOOL_DESCRIPTION}}';
    }

    public function schema(): array
    {
        return         [
            'type' => 'object',
            'properties' => [
                'limit' => ['type' => 'integer', 'description' => 'Number of records (max 100)'],
            ],
        ];
    }

    public function authorize(ChatContext $context): bool
    {
        {{AUTHORIZE_LOGIC}}
    }

    public function execute(array $arguments, ChatContext $context): ToolResult
    {
        {{EXECUTE_LOGIC}}
    }
}
