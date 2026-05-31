<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Permission;

class PermissionSearchTool implements ToolInterface
{
    public function name(): string
    {
        return 'permission_search';
    }

    public function description(): string
    {
        return 'Search Permission records by fillable fields';
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

        $query = Permission::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'LIKE', "%$search%");
                $q->orWhere('guard_name', 'LIKE', "%$search%");
                $q->orWhere('display_name', 'LIKE', "%$search%");
                $q->orWhere('group', 'LIKE', "%$search%");
            });
        }

        $records = $query->latest()->limit($limit)->get()->toArray();

        return ToolResult::success($records);
    }
}
