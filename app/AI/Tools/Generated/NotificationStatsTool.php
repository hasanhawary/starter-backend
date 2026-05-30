<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Notification;

class NotificationStatsTool implements ToolInterface
{
    public function name(): string
    {
        return 'notification_stats';
    }

    public function description(): string
    {
        return 'Get statistical overview of Notification records';
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
                $total = Notification::count();
        $stats = ['total' => $total];


        if (Notification::usesTimestamps()) {
            $stats['latest_created'] = Notification::latest()->value('created_at');
            $stats['oldest_created'] = Notification::oldest()->value('created_at');
        }

        return ToolResult::success($stats);
    }
}
