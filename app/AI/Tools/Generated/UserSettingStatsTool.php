<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\UserSetting;

class UserSettingStatsTool implements ToolInterface
{
    public function name(): string
    {
        return 'user_setting_stats';
    }

    public function description(): string
    {
        return 'Get statistical overview of UserSetting records';
    }

    public function schema(): array
    {
        return [
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
        $total = UserSetting::count();
        $stats = ['total' => $total];

        if (UserSetting::usesTimestamps()) {
            $stats['latest_created'] = UserSetting::latest()->value('created_at');
            $stats['oldest_created'] = UserSetting::oldest()->value('created_at');
        }

        return ToolResult::success($stats);
    }
}
