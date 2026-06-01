<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use AiChat\Support\SafeQueryBuilder;
use App\Models\Setting;

class SettingSummaryTool implements ToolInterface
{
    public function name(): string
    {
        return 'setting_summary';
    }

    public function description(): string
    {
        return 'Get numeric summary (avg, min, max, sum) for Setting records';
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
                'avg' => Setting::avg('id'),
                'min' => Setting::min('id'),
                'max' => Setting::max('id'),
                'sum' => Setting::sum('id'),
            ],
        ];

        return ToolResult::success($summary);
    }
}
