<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Pipeline\ChatPayload;
use AiChat\Policies\ChatContext;
use AiChat\Policies\PolicyManager;
use Closure;

class ApplyPolicies
{
    public function __construct(
        protected PolicyManager $policyManager,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $context = new ChatContext(
            action: 'chat',
            agent: $payload->agent,
            user: $payload->user,
            payload: [
                'message' => $payload->message,
                'conversation_id' => $payload->conversationId(),
            ],
        );

        $policies = $payload->agent?->policies() ?? [];

        $result = $this->policyManager->evaluate($context, $policies);

        if ($result->isDenied()) {
            $payload->addError($result->reason, 403);

            return $payload;
        }

        return $next($payload);
    }
}
