<?php

namespace AiChat\MCP;

use AiChat\Pipeline\ExecutionPlan;
use AiChat\Policies\ChatContext;

class ToolSelector
{
    public function __construct(
        protected ToolRegistry $registry,
    ) {}

    public function select(ExecutionPlan $plan, ChatContext $context): array
    {
        if (! $plan->requiresTools()) {
            return [];
        }

        $selectedNames = $plan->tools;

        if (empty($selectedNames)) {
            $selectedNames = $this->autoSelect($plan, $context);
        }

        $maxTools = (int) config('ai-chat.context.max_tools', 5);
        $authorized = [];

        foreach ($selectedNames as $name) {
            if (count($authorized) >= $maxTools) {
                break;
            }

            $tool = $this->registry->get($name);

            if (! $tool) {
                continue;
            }

            if (! $tool->authorize($context)) {
                continue;
            }

            $authorized[$name] = $tool;
        }

        return $authorized;
    }

    protected function autoSelect(ExecutionPlan $plan, ChatContext $context): array
    {
        $allTools = $this->registry->all();
        $matched = [];
        $intent = $plan->intent;
        $maxTools = (int) config('ai-chat.context.max_tools', 5);

        foreach ($allTools as $tool) {
            if (count($matched) >= $maxTools) {
                break;
            }

            if (! $tool->authorize($context)) {
                continue;
            }

            $tags = method_exists($tool, 'tags') ? $tool->tags() : [];

            if ($this->intentMatchesTags($intent, $tags)) {
                $matched[] = $tool->name();

                continue;
            }

            $keywords = method_exists($tool, 'keywords') ? $tool->keywords() : [];

            if (! empty($keywords)) {
                $matched[] = $tool->name();

                continue;
            }
        }

        return $matched;
    }

    protected function intentMatchesTags(string $intent, array $tags): bool
    {
        $intentTagMap = [
            'live_data' => ['users', 'orders', 'analytics', 'database', 'count', 'list'],
            'knowledge' => ['docs', 'knowledge', 'search'],
            'memory' => ['memory', 'conversation'],
            'analytics' => ['analytics', 'stats', 'reports', 'orders', 'revenue'],
            'mixed' => ['users', 'orders', 'analytics', 'database', 'docs', 'knowledge'],
        ];

        $targetTags = $intentTagMap[$intent] ?? [];

        if (empty($targetTags)) {
            return false;
        }

        foreach ($tags as $tag) {
            if (in_array($tag, $targetTags)) {
                return true;
            }
        }

        return false;
    }
}
