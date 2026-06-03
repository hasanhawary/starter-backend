<?php

namespace App\AI\Tools\Custom\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Permission;

class PermissionStatsTool implements ToolInterface
{
    public function name(): string
    {
        return 'permission_stats';
    }

    public function description(): string
    {
        return 'Get statistical overview of Permission records';
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
                $total = Permission::count();
        $stats = ['total' => $total];


        if (Permission::usesTimestamps()) {
            $stats['latest_created'] = Permission::latest()->value('created_at');
            $stats['oldest_created'] = Permission::oldest()->value('created_at');
        }

        return ToolResult::success($stats);
    }
}
