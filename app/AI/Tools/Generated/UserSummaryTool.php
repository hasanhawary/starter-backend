<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\User;

class UserSummaryTool implements ToolInterface
{
    public function name(): string
    {
        return 'user_summary';
    }

    public function description(): string
    {
        return 'Get numeric summary (avg, min, max, sum) for User records';
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
                'avg' => User::avg('id'),
                'min' => User::min('id'),
                'max' => User::max('id'),
                'sum' => User::sum('id'),
            ],
        ];

        return ToolResult::success($summary);
    }
}
