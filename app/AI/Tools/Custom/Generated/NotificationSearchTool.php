<?php

namespace App\AI\Tools\Custom\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Notification;

class NotificationSearchTool implements ToolInterface
{
    public function name(): string
    {
        return 'notification_search';
    }

    public function description(): string
    {
        return 'Search Notification records by fillable fields';
    }

    public function schema(): array
    {
        return         [
            'type' => 'object',
            'properties' => [
                'search' => ['type' => 'string', 'description' => 'Search term'],
                'limit' => ['type' => 'integer', 'description' => 'Max results (default 10, max 100)'],
            ],
        ];
    }

    public function authorize(ChatContext $context): bool
    {
        return $context->action === 'read';
    }

    public function execute(array $arguments, ChatContext $context): ToolResult
    {
                $limit = min((int) ($arguments['limit'] ?? 10), 100);
        $search = $arguments['search'] ?? '';

        $query = Notification::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->orWhere('type', 'LIKE', "%$search%");
                $q->orWhere('notifiable_type', 'LIKE', "%$search%");
                $q->orWhere('notifiable_id', 'LIKE', "%$search%");
                $q->orWhere('data', 'LIKE', "%$search%");
                $q->orWhere('read_at', 'LIKE', "%$search%");
                $q->orWhere('open_at', 'LIKE', "%$search%");
            });
        }

        $records = $query->latest()->limit($limit)->get()->toArray();

        return ToolResult::success($records);
    }
}
