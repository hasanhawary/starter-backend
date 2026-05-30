<?php

namespace App\AI\Policies;

use AiChat\Contracts\ChatPolicyInterface;
use AiChat\Policies\ChatContext;
use AiChat\Policies\PolicyResult;

class ProjectChatPolicy implements ChatPolicyInterface
{
    protected array $allowedActions = ['read', 'search', 'count', 'stats'];

    protected array $blockedModels = [
        'App\\Models\\PersonalAccessToken',
        'App\\Models\\PasswordResetToken',
    ];

    protected array $blockedFields = [
        'password',
        'remember_token',
        'token',
        'secret',
        'api_key',
    ];

    protected int $maxRecords = 100;

    protected bool $requireAuth = false;

    public function authorize(ChatContext $context): PolicyResult
    {
        if ($this->requireAuth && $context->user === null) {
            return PolicyResult::denied('Authentication is required.', self::class);
        }

        if (! in_array($context->action, $this->allowedActions, true)) {
            return PolicyResult::denied("The action [{$context->action}] is not allowed by this policy.", self::class);
        }

        if (isset($context->payload['model']) && in_array($context->payload['model'], $this->blockedModels, true)) {
            return PolicyResult::denied('Access to this model is blocked by policy.', self::class);
        }

        if (isset($context->payload['fields'])) {
            $blocked = array_intersect($context->payload['fields'], $this->blockedFields);

            if (! empty($blocked)) {
                return PolicyResult::denied('Access to certain fields is blocked by policy.', self::class);
            }
        }

        return PolicyResult::allowed('Action permitted by policy.', self::class);
    }
}
