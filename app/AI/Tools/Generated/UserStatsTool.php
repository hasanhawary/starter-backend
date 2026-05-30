<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\User;

class UserStatsTool implements ToolInterface
{
    public function name(): string
    {
        return 'user_stats';
    }

    public function description(): string
    {
        return 'Get statistical overview of User records';
    }

    public function schema(): array
    {
        return [
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
        $total = User::count();
        $stats = ['total' => $total];

        $stats['trashed'] = User::onlyTrashed()->count();

        if (User::usesTimestamps()) {
            $stats['latest_created'] = User::latest()->value('created_at');
            $stats['oldest_created'] = User::oldest()->value('created_at');
        }

        return ToolResult::success($stats);
    }
}
