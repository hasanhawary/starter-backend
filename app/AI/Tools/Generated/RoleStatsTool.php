<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Role;

class RoleStatsTool implements ToolInterface
{
    public function name(): string
    {
        return 'role_stats';
    }

    public function description(): string
    {
        return 'Get statistical overview of Role records';
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
                $total = Role::count();
        $stats = ['total' => $total];


        if (Role::usesTimestamps()) {
            $stats['latest_created'] = Role::latest()->value('created_at');
            $stats['oldest_created'] = Role::oldest()->value('created_at');
        }

        return ToolResult::success($stats);
    }
}
