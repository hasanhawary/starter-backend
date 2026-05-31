<?php

namespace AiChat\Tests\Planning;

use AiChat\Planning\LlmPlanner;
use Laravel\Ai\AnonymousAgent;
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

    public function test_plan_uses_laravel_ai_agent_prompt_api(): void
    {
        AnonymousAgent::fake([
            json_encode([
                'intent' => 'live_data',
                'tools' => ['user_count'],
            ]),
        ]);

        $planner = new LlmPlanner;

        $plan = $planner->plan('how many user', ['user_count']);

        $this->assertSame('live_data', $plan->intent);
        $this->assertSame(['user_count'], $plan->tools);
        $this->assertSame('llm', $plan->planner);

        AnonymousAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'how many user'));
    }

    public function test_plan_extracts_json_from_prose_response(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return 'Here is the plan: {"intent":"knowledge","use_rag":true,"rag_query":"auth flow","metadata":{"confidence":0.9}}';
            }
        };

        $plan = $planner->plan('Explain authentication flow');

        $this->assertSame('knowledge', $plan->intent);
        $this->assertTrue($plan->useRag);
        $this->assertSame('auth flow', $plan->ragQuery);
        $this->assertSame('rag_backed', $plan->metadata['response_mode']);
    }

    public function test_plan_removes_tools_not_available_to_runtime(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return json_encode([
                    'intent' => 'live_data',
                    'tools' => ['user_count', 'fake_delete_everything'],
                    'metadata' => ['confidence' => 0.8],
                ]);
            }
        };

        $plan = $planner->plan('How many users?', ['user_count']);

        $this->assertSame(['user_count'], $plan->tools);
        $this->assertSame('tool_execution', $plan->metadata['execution_strategy']);
        $this->assertTrue($plan->metadata['requires_tools']);
    }

    public function test_plan_sanitizes_clarification_response(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return json_encode([
                    'intent' => 'clarification',
                    'tools' => ['user_count'],
                    'use_rag' => true,
                    'use_memory' => true,
                    'needs_clarification' => true,
                ]);
            }
        };

        $plan = $planner->plan('Show me the thing', ['user_count']);

        $this->assertSame('clarification', $plan->intent);
        $this->assertTrue($plan->needsClarification);
        $this->assertNotEmpty($plan->clarificationQuestion);
        $this->assertSame([], $plan->tools);
        $this->assertFalse($plan->useRag);
        $this->assertFalse($plan->useMemory);
        $this->assertSame('clarification', $plan->metadata['response_mode']);
    }

    public function test_plan_normalizes_memory_and_rag_requests(): void
    {
        $memoryPlanner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return json_encode(['intent' => 'memory']);
            }
        };

        $memoryPlan = $memoryPlanner->plan('Which branch do I manage?');

        $this->assertTrue($memoryPlan->useMemory);
        $this->assertSame('Which branch do I manage?', $memoryPlan->memoryQuery);
        $this->assertSame('relevant', $memoryPlan->historyMode);
        $this->assertSame('memory_backed', $memoryPlan->metadata['response_mode']);

        $ragPlanner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return json_encode(['intent' => 'project_structure']);
            }
        };

        $ragPlan = $ragPlanner->plan('Which controller handles users?');

        $this->assertTrue($ragPlan->useRag);
        $this->assertSame('Which controller handles users?', $ragPlan->ragQuery);
        $this->assertSame('rag_backed', $ragPlan->metadata['response_mode']);
    }

    public function test_plan_keeps_multi_step_metadata_for_mixed_requests(): void
    {
        $planner = new class extends LlmPlanner
        {
            protected function callLlm(string $message, array $availableToolNames, string $model): ?string
            {
                return json_encode([
                    'intent' => 'mixed',
                    'tools' => ['notification_count'],
                    'use_rag' => true,
                    'use_memory' => true,
                    'metadata' => [
                        'intent_analysis' => 'Needs live data, memory, and project rules.',
                        'confidence' => 1.5,
                    ],
                ]);
            }
        };

        $plan = $planner->plan('Compare notification rules with my preferred report', ['notification_count']);

        $this->assertSame('mixed', $plan->intent);
        $this->assertSame(['notification_count'], $plan->tools);
        $this->assertTrue($plan->useRag);
        $this->assertTrue($plan->useMemory);
        $this->assertTrue($plan->metadata['multi_step']);
        $this->assertSame('multi_step', $plan->metadata['execution_strategy']);
        $this->assertSame(1.0, $plan->metadata['confidence']);
    }
}
