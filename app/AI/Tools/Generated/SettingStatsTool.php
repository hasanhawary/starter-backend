<?php

namespace App\AI\Tools\Generated;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;
use App\Models\Setting;

class SettingStatsTool implements ToolInterface
{
    public function name(): string
    {
        return 'setting_stats';
    }

    public function description(): string
    {
        return 'Get statistical overview of Setting records';
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
                $total = Setting::count();
        $stats = ['total' => $total];


        if (Setting::usesTimestamps()) {
            $stats['latest_created'] = Setting::latest()->value('created_at');
            $stats['oldest_created'] = Setting::oldest()->value('created_at');
        }

        return ToolResult::success($stats);
    }
}
