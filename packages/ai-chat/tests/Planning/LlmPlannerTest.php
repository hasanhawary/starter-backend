<?php

namespace AiChat\Tests\Planning;

use AiChat\Planning\LlmPlanner;
use Tests\TestCase;

class LlmPlannerTest extends TestCase
{
    public function test_plan_returns_null_on_invalid_json(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return 'this is not json at all';
            }
        };

        $plan = $planner->plan('test message');

        $this->assertNotNull($plan);
        $this->assertSame('direct', $plan->intent);
        $this->assertSame('llm_fallback', $plan->planner);
    }

    public function test_plan_parses_valid_json(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return json_encode([
                    'intent' => 'mixed',
                    'tools' => ['users_count', 'orders_summary'],
                    'use_rag' => true,
                    'rag_query' => 'revenue calculation',
                    'rag_limit' => 5,
                    'use_memory' => false,
                    'history_limit' => 10,
                ]);
            }
        };

        $plan = $planner->plan('Compare orders', ['users_count', 'orders_summary']);

        $this->assertSame('mixed', $plan->intent);
        $this->assertSame(['users_count', 'orders_summary'], $plan->tools);
        $this->assertTrue($plan->useRag);
        $this->assertSame('revenue calculation', $plan->ragQuery);
        $this->assertSame(5, $plan->ragLimit);
        $this->assertFalse($plan->useMemory);
        $this->assertSame(10, $plan->historyLimit);
        $this->assertSame('llm', $plan->planner);
    }

    public function test_plan_sanitizes_invalid_intent(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return json_encode([
                    'intent' => 'totally_invalid_intent',
                    'tools' => ['tool1'],
                    'rag_limit' => 99,
                    'memory_limit' => -5,
                    'history_limit' => 100,
                ]);
            }
        };

        $plan = $planner->plan('test');

        $this->assertSame('direct', $plan->intent);
        $this->assertLessThanOrEqual(10, $plan->ragLimit);
        $this->assertGreaterThanOrEqual(1, $plan->memoryLimit);
        $this->assertLessThanOrEqual(20, $plan->historyLimit);
    }

    public function test_plan_trims_tools_to_max(): void
    {
        $manyTools = [];
        for ($i = 1; $i <= 20; $i++) {
            $manyTools[] = "tool_{$i}";
        }

        $planner = new class($manyTools) extends LlmPlanner
        {
            private array $tools;

            public function __construct(array $tools)
            {
                $this->tools = $tools;
            }

            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return json_encode(['intent' => 'live_data', 'tools' => $this->tools]);
            }
        };

        $plan = $planner->plan('test', $manyTools);

        $this->assertLessThanOrEqual(5, count($plan->tools));
    }

    public function test_plan_strips_markdown_from_response(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return '```json
{"intent": "knowledge", "use_rag": true}
```';
            }
        };

        $plan = $planner->plan('test');

        $this->assertNotNull($plan);
        $this->assertTrue(in_array($plan->intent, ['knowledge', 'direct']));
    }

    public function test_plan_returns_null_on_llm_failure(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return null;
            }
        };

        $plan = $planner->plan('test message');

        $this->assertNull($plan);
    }
}
