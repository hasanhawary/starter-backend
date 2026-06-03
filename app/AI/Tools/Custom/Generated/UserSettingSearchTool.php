<?php

namespace App\AI\Tools\Custom\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\UserSetting;

class UserSettingSearchTool implements ToolInterface
{
    public function name(): string
    {
        return 'user_setting_search';
    }

    public function description(): string
    {
        return 'Search UserSetting records by fillable fields';
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

        $query = UserSetting::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->orWhere('user_id', 'LIKE', "%$search%");
                $q->orWhere('setting', 'LIKE', "%$search%");
            });
        }

        $records = $query->latest()->limit($limit)->get()->toArray();

        return ToolResult::success($records);
    }
}
