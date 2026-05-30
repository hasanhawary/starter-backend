<?php

namespace AiChat\MCP;

use AiChat\Contracts\ToolInterface;
use AiChat\Policies\ChatContext;
use AiChat\Policies\PolicyResult;

class ToolPermissionGuard
{
    public function check(ToolInterface $tool, ChatContext $context): PolicyResult
    {
        if (! $tool->authorize($context)) {
            return $this->deny("Tool [{$tool->name()}] authorization denied for current context.");
        }

        $blockedActions = config('ai-chat.policies.blocked_actions', []);

        if (in_array($tool->name(), $blockedActions)) {
            return $this->deny("Tool [{$tool->name()}] is blocked by policy configuration.");
        }

        $readOnly = config('ai-chat.policies.read_only', false);

        if ($readOnly && $context->isWriteAction()) {
            return $this->deny('Write operations are disabled in read-only mode.');
        }

        if ($context->agent && $context->agent->isReadOnly() && $context->isWriteAction()) {
            return $this->deny("Agent [{$context->agent->name()}] is read-only and cannot execute write tools.");
        }

        return PolicyResult::allowed();
    }

    public function deny(string $reason): PolicyResult
    {
        return PolicyResult::denied($reason, static::class);
    }
}
