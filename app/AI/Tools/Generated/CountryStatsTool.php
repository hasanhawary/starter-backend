<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use AiChat\Support\SafeQueryBuilder;
use App\Models\Country;

class CountryStatsTool implements ToolInterface
{
    public function name(): string
    {
        return 'country_stats';
    }

    public function description(): string
    {
        return 'Get statistical overview of Country records';
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
                $total = Country::count();
        $stats = ['total' => $total];

        $stats['trashed'] = Country::onlyTrashed()->count();

        if (Country::usesTimestamps()) {
            $stats['latest_created'] = Country::latest()->value('created_at');
            $stats['oldest_created'] = Country::oldest()->value('created_at');
        }

        return ToolResult::success($stats);
    }
}
