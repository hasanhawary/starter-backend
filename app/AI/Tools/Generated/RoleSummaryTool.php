<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use AiChat\Support\SafeQueryBuilder;
use App\Models\Role;

class RoleSummaryTool implements ToolInterface
{
    public function name(): string
    {
        return 'role_summary';
    }

    public function description(): string
    {
        return 'Get numeric summary (avg, min, max, sum) for Role records';
    }

    public function schema(): array
    {
        return         [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function authorize(ChatContext $context): bool
    {
        return $context->action === 'read';
    }

    public function execute(array $arguments, ChatContext $context): ToolResult
    {
                $summary = [
            'id' => [
                'avg' => Role::avg('id'),
                'min' => Role::min('id'),
                'max' => Role::max('id'),
                'sum' => Role::sum('id'),
            ],
        ];

        return ToolResult::success($summary);
    }
}
