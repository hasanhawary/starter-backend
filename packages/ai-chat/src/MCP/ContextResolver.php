<?php

namespace AiChat\MCP;

use AiChat\Contracts\ContextProviderInterface;
use AiChat\Policies\ChatContext;
use AiChat\Support\TokenCounter;
use Illuminate\Support\Facades\App;

class ContextResolver
{
    public function resolve(array $providerClasses, ChatContext $context): array
    {
        $budget = config('ai-chat.context.token_budget', 4000);
        $used = 0;
        $resolved = [];

        foreach ($providerClasses as $providerClass) {
            if (! $this->isValidProvider($providerClass)) {
                continue;
            }

            $provider = App::make($providerClass);

            $provided = $provider->provide();

            $estimatedTokens = TokenCounter::estimate(json_encode($provided));

            if (! TokenCounter::withinBudget($used + $estimatedTokens, $budget)) {
                continue;
            }

            $used += $estimatedTokens;
            $resolved[$provider->name()] = $provided;
        }

        return $resolved;
    }

    protected function isValidProvider(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        return is_subclass_of($class, ContextProviderInterface::class)
            || $class === ContextProviderInterface::class;
    }
}
