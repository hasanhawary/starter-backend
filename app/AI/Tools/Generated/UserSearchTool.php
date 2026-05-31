<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\User;

class UserSearchTool implements ToolInterface
{
    public function name(): string
    {
        return 'user_search';
    }

    public function description(): string
    {
        return 'Search User records by fillable fields';
    }

    public function schema(): array
    {
        return [
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

        $query = User::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'LIKE', "%$search%");
                $q->orWhere('email', 'LIKE', "%$search%");
                $q->orWhere('phone_code_id', 'LIKE', "%$search%");
                $q->orWhere('phone', 'LIKE', "%$search%");
                $q->orWhere('avatar', 'LIKE', "%$search%");
                $q->orWhere('gender', 'LIKE', "%$search%");
                $q->orWhere('otp_data', 'LIKE', "%$search%");
                $q->orWhere('is_active', 'LIKE', "%$search%");
                $q->orWhere('last_login', 'LIKE', "%$search%");
                $q->orWhere('ldap_name', 'LIKE', "%$search%");
                $q->orWhere('guid', 'LIKE', "%$search%");
                $q->orWhere('uid', 'LIKE', "%$search%");
                $q->orWhere('created_by', 'LIKE', "%$search%");
            });
        }

        $records = $query->latest()->limit($limit)->get()->toArray();

        return ToolResult::success($records);
    }
}
