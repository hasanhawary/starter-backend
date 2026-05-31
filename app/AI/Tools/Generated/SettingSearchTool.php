<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Setting;

class SettingSearchTool implements ToolInterface
{
    public function name(): string
    {
        return 'setting_search';
    }

    public function description(): string
    {
        return 'Search Setting records by fillable fields';
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

        $query = Setting::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->orWhere('key', 'LIKE', "%$search%");
                $q->orWhere('value', 'LIKE', "%$search%");
                $q->orWhere('group', 'LIKE', "%$search%");
                $q->orWhere('type', 'LIKE', "%$search%");
                $q->orWhere('label', 'LIKE', "%$search%");
                $q->orWhere('placeholder', 'LIKE', "%$search%");
                $q->orWhere('is_multi_lang', 'LIKE', "%$search%");
                $q->orWhere('is_env', 'LIKE', "%$search%");
            });
        }

        $records = $query->latest()->limit($limit)->get()->toArray();

        return ToolResult::success($records);
    }
}
