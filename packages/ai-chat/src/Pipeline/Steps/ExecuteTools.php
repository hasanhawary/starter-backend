<?php

namespace AiChat\Pipeline\Steps;

use AiChat\MCP\ToolExecutor;
use AiChat\Pipeline\ChatPayload;
use AiChat\Policies\ChatContext;
use Closure;

class ExecuteTools
{
    public function __construct(
        protected ToolExecutor $toolExecutor,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $rawResponse = $payload->rawResponse;

        if ($rawResponse === null) {
            return $next($payload);
        }

        $toolCalls = $rawResponse['tool_calls'] ?? [];

        if (empty($toolCalls)) {
            return $next($payload);
        }

        $context = new ChatContext(
            action: 'read',
            agent: $payload->agent,
            user: $payload->user,
            payload: [
                'message' => $payload->message,
                'conversation_id' => $payload->conversationId(),
            ],
        );

        foreach ($toolCalls as $toolCall) {
            $name = $toolCall['name'] ?? $toolCall['function']['name'] ?? null;
            $arguments = $toolCall['arguments'] ?? $toolCall['function']['arguments'] ?? [];

            if (is_string($arguments)) {
                $arguments = json_decode($arguments, true) ?? [];
            }

            if ($name === null) {
                continue;
            }

            $result = $this->toolExecutor->execute($name, $arguments, $context);

            $payload->addToolResult($name, $result);
        }

        return $next($payload);
    }
}
