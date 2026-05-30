<?php

namespace AiChat\Policies;

use AiChat\Contracts\ChatPolicyInterface;

class DefaultReadOnlyPolicy implements ChatPolicyInterface
{
    public function authorize(ChatContext $context): PolicyResult
    {
        if ($context->agent?->isReadOnly() && $context->isWriteAction()) {
            return PolicyResult::denied(
                'The current agent operates in read-only mode. Write actions are not permitted.',
                self::class,
            );
        }

        $blockedActions = config('ai-chat.policies.blocked_actions', []);

        if (in_array($context->action, $blockedActions)) {
            return PolicyResult::denied(
                "The action [{$context->action}] is blocked by policy.",
                self::class,
            );
        }

        return PolicyResult::allowed('Action permitted by read-only policy.', self::class);
    }
}
