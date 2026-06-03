<?php

namespace App\AI\Tools\Custom\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Permission;

class PermissionSummaryTool implements ToolInterface
{
    public function name(): string
    {
        return 'permission_summary';
    }

    public function description(): string
    {
        return 'Get numeric summary (avg, min, max, sum) for Permission records';
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
                'avg' => Permission::avg('id'),
                'min' => Permission::min('id'),
                'max' => Permission::max('id'),
                'sum' => Permission::sum('id'),
            ],
        ];

        return ToolResult::success($summary);
    }
}
