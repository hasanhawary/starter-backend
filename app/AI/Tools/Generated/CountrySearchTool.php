<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Country;

class CountrySearchTool implements ToolInterface
{
    public function name(): string
    {
        return 'country_search';
    }

    public function description(): string
    {
        return 'Search Country records by fillable fields';
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

        $query = Country::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'LIKE', "%$search%");
                $q->orWhere('nationality', 'LIKE', "%$search%");
                $q->orWhere('flag', 'LIKE', "%$search%");
                $q->orWhere('code', 'LIKE', "%$search%");
                $q->orWhere('phone_code', 'LIKE', "%$search%");
                $q->orWhere('phone_length', 'LIKE', "%$search%");
                $q->orWhere('is_active', 'LIKE', "%$search%");
            });
        }

        $records = $query->latest()->limit($limit)->get()->toArray();

        return ToolResult::success($records);
    }
}
