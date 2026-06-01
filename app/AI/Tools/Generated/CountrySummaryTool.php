<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use AiChat\Support\SafeQueryBuilder;
use App\Models\Country;

class CountrySummaryTool implements ToolInterface
{
    public function name(): string
    {
        return 'country_summary';
    }

    public function description(): string
    {
        return 'Get numeric summary (avg, min, max, sum) for Country records';
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
                $summary = [
            'id' => [
                'avg' => Country::avg('id'),
                'min' => Country::min('id'),
                'max' => Country::max('id'),
                'sum' => Country::sum('id'),
            ],
        ];

        return ToolResult::success($summary);
    }
}
