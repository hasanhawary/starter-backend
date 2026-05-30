<?php

namespace AiChat\Pipeline\Steps;

use AiChat\MCP\ToolRegistry;
use AiChat\MCP\ToolSelector;
use AiChat\Pipeline\ChatPayload;
use AiChat\Policies\ChatContext;
use Closure;

class ResolveTools
{
    public function __construct(
        protected ToolRegistry $registry,
        protected ToolSelector $selector,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if ($payload->executionPlan && ! $payload->executionPlan->requiresTools()) {
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

        if ($payload->executionPlan) {
            $resolved = $this->selector->select($payload->executionPlan, $context);
        } else {
            $resolved = $this->resolveAllAgentTools($payload, $context);
        }

        $maxTools = (int) config('ai-chat.context.max_tools', 5);

        if (count($resolved) > $maxTools) {
            $resolved = array_slice($resolved, 0, $maxTools, true);
        }

        $payload->tools = $resolved;

        return $next($payload);
    }

    protected function resolveAllAgentTools(ChatPayload $payload, ChatContext $context): array
    {
        $toolNames = $payload->agent?->tools() ?? [];

        if (empty($toolNames)) {
            return [];
        }

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

        return $resolved;
    }
}
