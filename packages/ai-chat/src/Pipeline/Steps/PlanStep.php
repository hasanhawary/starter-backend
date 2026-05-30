<?php

namespace AiChat\Pipeline\Steps;

use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ChatPayload;
use AiChat\Planning\HybridPlanner;
use Closure;

class PlanStep
{
    public function __construct(
        protected HybridPlanner $planner,
        protected ToolRegistry $toolRegistry,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $availableTools = $this->resolveAvailableTools($payload);

        $plan = $this->planner->plan(
            $payload->message,
            $availableTools,
            $payload->metadata['locale'] ?? null,
        );

        $payload->executionPlan = $plan;
        $payload->setMetadata('planner', $plan->planner);
        $payload->setMetadata('intent', $plan->intent);

        return $next($payload);
    }

    protected function resolveAvailableTools(ChatPayload $payload): array
    {
        $agentToolNames = $payload->agent?->tools() ?? [];

        if (empty($agentToolNames)) {
            $enabled = config('ai-chat.tools.enabled', []);

            if ($enabled === ['*'] || in_array('*', $enabled) || empty($enabled)) {
                return array_values($this->toolRegistry->all());
            }

            $tools = [];

            foreach ($enabled as $name) {
                $tool = $this->toolRegistry->get($name);

                if ($tool) {
                    $tools[] = $tool;
                }
            }

            return $tools;
        }

        $tools = [];

        foreach ($agentToolNames as $name) {
            $tool = $this->toolRegistry->get($name);

            if ($tool) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }
}
