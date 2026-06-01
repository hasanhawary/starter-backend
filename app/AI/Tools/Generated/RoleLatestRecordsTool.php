<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use AiChat\Support\SafeQueryBuilder;
use App\Models\Role;

class RoleLatestRecordsTool implements ToolInterface
{
    public function name(): string
    {
        return 'role_latest';
    }

    public function description(): string
    {
        return 'Get latest Role records';
    }

    public function schema(): array
    {
        return         [
            'type' => 'object',
            'properties' => [
                'limit' => ['type' => 'integer', 'description' => 'Number of records (max 100)'],
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

        $records = Role::query()
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();

        return ToolResult::success($records);
    }
}
