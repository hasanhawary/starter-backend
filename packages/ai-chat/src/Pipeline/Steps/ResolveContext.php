<?php

namespace AiChat\Pipeline\Steps;

use AiChat\MCP\ContextResolver;
use AiChat\Pipeline\ChatPayload;
use AiChat\Policies\ChatContext;
use Closure;

class ResolveContext
{
    public function __construct(
        protected ContextResolver $contextResolver,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $providerClasses = $payload->agent?->contextProviders() ?? [];

        if (empty($providerClasses)) {
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

        $resolved = $this->contextResolver->resolve($providerClasses, $context);

        foreach ($resolved as $key => $value) {
            $payload->setContext($key, $value);
        }

        return $next($payload);
    }
}
