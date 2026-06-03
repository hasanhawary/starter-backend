<?php

namespace App\AI\Tools\Custom\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\UserSetting;

class UserSettingSummaryTool implements ToolInterface
{
    public function name(): string
    {
        return 'user_setting_summary';
    }

    public function description(): string
    {
        return 'Get numeric summary (avg, min, max, sum) for UserSetting records';
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
                'avg' => UserSetting::avg('id'),
                'min' => UserSetting::min('id'),
                'max' => UserSetting::max('id'),
                'sum' => UserSetting::sum('id'),
            ],
        ];

        return ToolResult::success($summary);
    }
}
