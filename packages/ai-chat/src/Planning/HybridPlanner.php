<?php

namespace AiChat\Planning;

use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ExecutionPlan;
use Illuminate\Support\Facades\Cache;

class HybridPlanner
{
    public function __construct(
        protected HeuristicPlanner $heuristicPlanner,
        protected LlmPlanner $llmPlanner,
        protected ToolRegistry $toolRegistry,
    ) {}

    public function plan(string $message, array $availableTools = [], ?string $locale = null): ExecutionPlan
    {
        $mode = config('ai-chat.planning.mode', 'hybrid');
        $cachePlans = config('ai-chat.planning.cache_plans', true);

        if ($cachePlans) {
            $cacheKey = 'ai-chat:plan:'.md5(mb_strtolower(trim($message)).'|'.implode(',', array_map(fn ($t) => method_exists($t, 'name') ? $t->name() : (string) $t, $availableTools)));

            $cached = Cache::get($cacheKey);

            if ($cached && is_array($cached)) {
                return ExecutionPlan::fromArray($cached);
            }
        }

        $plan = match ($mode) {
            'heuristic' => $this->heuristicOnly($message, $availableTools, $locale),
            'llm' => $this->llmOnly($message, $availableTools, $locale),
            default => $this->hybrid($message, $availableTools, $locale),
        };

        $plan = $this->validateAndSanitize($plan);

        if (isset($cacheKey) && $cachePlans) {
            Cache::put($cacheKey, $plan->toArray(), config('ai-chat.planning.cache_ttl', 3600));
        }

        return $plan;
    }

    protected function heuristicOnly(string $message, array $availableTools, ?string $locale): ExecutionPlan
    {
        return $this->heuristicPlanner->plan($message, $availableTools, $locale);
    }

    protected function llmOnly(string $message, array $availableTools, ?string $locale): ExecutionPlan
    {
        $toolNames = array_map(
            fn ($t) => method_exists($t, 'name') ? $t->name() : (string) $t,
            $availableTools,
        );

        $plan = $this->llmPlanner->plan($message, $toolNames, $locale);

        return $plan ?? $this->heuristicPlanner->plan($message, $availableTools, $locale);
    }

    protected function hybrid(string $message, array $availableTools, ?string $locale): ExecutionPlan
    {
        $heuristicPlan = $this->heuristicPlanner->plan($message, $availableTools, $locale);

        if (! $this->heuristicPlanner->shouldUseLlm($heuristicPlan)) {
            return $heuristicPlan;
        }

        $toolNames = array_map(
            fn ($t) => method_exists($t, 'name') ? $t->name() : (string) $t,
            $availableTools,
        );

        $plan = $this->llmPlanner->plan($message, $toolNames, $locale);

        if ($plan === null) {
            return $heuristicPlan;
        }

        return $plan;
    }

    protected function validateAndSanitize(ExecutionPlan $plan): ExecutionPlan
    {
        $validIntents = ['live_data', 'knowledge', 'memory', 'project_structure', 'mixed', 'direct', 'clarification'];

        if (! in_array($plan->intent, $validIntents)) {
            $plan->intent = 'direct';
        }

        $maxTools = (int) config('ai-chat.context.max_tools', 5);

        if (count($plan->tools) > $maxTools) {
            $plan->tools = array_slice($plan->tools, 0, $maxTools);
        }

        $allToolNames = array_keys($this->toolRegistry->all());

        $plan->tools = array_filter($plan->tools, fn ($name) => in_array($name, $allToolNames));
        $plan->tools = array_values($plan->tools);

        $plan->ragLimit = max(1, min((int) $plan->ragLimit, 10));
        $plan->memoryLimit = max(1, min((int) $plan->memoryLimit, 10));
        $plan->historyLimit = max(0, min((int) $plan->historyLimit, 20));

        if ($plan->needsClarification && empty($plan->clarificationQuestion)) {
            $plan->needsClarification = false;
        }

        return $plan;
    }
}
