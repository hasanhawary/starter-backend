<?php

namespace AiChat\Pipeline\Steps;

use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ChatPayload;
use AiChat\Policies\ChatContext;
use Closure;

class ResolveTools
{
    public function __construct(
        protected ToolRegistry $registry,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $toolNames = $payload->agent?->tools() ?? [];

        if (empty($toolNames)) {
            return $next($payload);
        }

        $context = new ChatContext(
            action: 'chat',
            agent: $payload->agent,
            user: $payload->user,
            payload: [
                'message' => $payload->message,
                'conversation_id' => $payload->conversationId(),
            ],
        );

        $resolved = [];

        foreach ($toolNames as $toolName) {
            $tool = $this->registry->get($toolName);

            if ($tool === null) {
                continue;
            }

            if (! $tool->authorize($context)) {
                continue;
            }

            $resolved[$toolName] = $tool;
        }

        $payload->tools = $resolved;

        return $next($payload);
    }
}
