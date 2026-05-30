<?php

namespace AiChat\Policies;

use AiChat\Contracts\ChatPolicyInterface;

class PolicyManager
{
    protected array $policies = [];

    public function __construct(array $policies = [])
    {
        foreach ($policies as $policy) {
            $this->register($policy);
        }
    }

    public function register(ChatPolicyInterface $policy): void
    {
        $this->policies[$policy::class] = $policy;
    }

    public function evaluate(ChatContext $context, ?array $policyClasses = null): PolicyResult
    {
        $policies = $policyClasses !== null
            ? array_filter($this->policies, fn (ChatPolicyInterface $policy, string $class) => in_array($class, $policyClasses), ARRAY_FILTER_USE_BOTH)
            : $this->policies;

        foreach ($policies as $policy) {
            $result = $policy->authorize($context);

            if ($result->isDenied()) {
                return $result;
            }
        }

        return PolicyResult::allowed('All evaluated policies passed.');
    }

    public function evaluateAll(ChatContext $context): PolicyResult
    {
        return $this->evaluate($context);
    }
}
