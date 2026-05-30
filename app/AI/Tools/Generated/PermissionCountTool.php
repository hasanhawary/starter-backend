<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Permission;

class PermissionCountTool implements ToolInterface
{
    public function name(): string
    {
        return 'permission_count';
    }

    public function description(): string
    {
        return 'Count Permission records with optional filters';
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
                $query = Permission::query();

        if (!empty($arguments['filters'])) {
            foreach ($arguments['filters'] as $column => $value) {
                if (SafeQueryBuilder::isSafeColumn(new Permission, $column)) {
                    $query->where($column, $value);
                }
            }
        }

        return ToolResult::success(['count' => $query->count()]);
    }
}
