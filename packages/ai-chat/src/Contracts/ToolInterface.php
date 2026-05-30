<?php

namespace AiChat\Contracts;

use AiChat\MCP\ToolResult;
use AiChat\Policies\ChatContext;

interface ToolInterface
{
    public function name(): string;

    public function description(): string;

    public function schema(): array;

    public function authorize(ChatContext $context): bool;

    public function execute(array $arguments, ChatContext $context): ToolResult;
}
