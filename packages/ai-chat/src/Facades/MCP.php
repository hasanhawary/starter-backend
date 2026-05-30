<?php

namespace AiChat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AiChat\Contracts\ToolInterface get(string $name)
 * @method static void register(\AiChat\Contracts\ToolInterface $tool)
 * @method static array all()
 * @method static array schemas()
 * @method static array names()
 * @method static bool has(string $name)
 * @method static \AiChat\MCP\ToolResult execute(string $tool, array $arguments, \AiChat\Policies\ChatContext $context)
 */
class MCP extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai-chat.mcp';
    }
}
