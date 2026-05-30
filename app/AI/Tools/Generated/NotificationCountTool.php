<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Notification;

class NotificationCountTool implements ToolInterface
{
    public function name(): string
    {
        return 'notification_count';
    }

    public function description(): string
    {
        return 'Count Notification records with optional filters';
    }

    public function schema(): array
    {
        return         [
            'type' => 'object',
            'properties' => [
                'filters' => ['type' => 'object', 'description' => 'Key-value filters on fillable columns'],
            ],
        ];
    }

    public function authorize(ChatContext $context): bool
    {
        return $context->action === 'read';
    }

    public function execute(array $arguments, ChatContext $context): ToolResult
    {
                $query = Notification::query();

        if (!empty($arguments['filters'])) {
            foreach ($arguments['filters'] as $column => $value) {
                if (SafeQueryBuilder::isSafeColumn(new Notification, $column)) {
                    $query->where($column, $value);
                }
            }
        }

        return ToolResult::success(['count' => $query->count()]);
    }
}
