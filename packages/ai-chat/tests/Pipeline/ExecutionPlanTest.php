<?php

namespace AiChat\Tests\Pipeline;

use AiChat\Pipeline\ExecutionPlan;
use PHPUnit\Framework\TestCase;

class ExecutionPlanTest extends TestCase
{
    public function test_default_values(): void
    {
        $plan = new ExecutionPlan;

        $this->assertSame('direct', $plan->intent);
        $this->assertEmpty($plan->tools);
        $this->assertFalse($plan->useRag);
        $this->assertNull($plan->ragQuery);
        $this->assertSame(3, $plan->ragLimit);
        $this->assertFalse($plan->useMemory);
        $this->assertNull($plan->memoryQuery);
        $this->assertSame(3, $plan->memoryLimit);
        $this->assertSame(6, $plan->historyLimit);
        $this->assertFalse($plan->needsClarification);
        $this->assertNull($plan->clarificationQuestion);
        $this->assertEmpty($plan->metadata);
        $this->assertSame('heuristic', $plan->planner);
    }

    public function test_from_array_creates_plan(): void
    {
        $data = [
            'intent' => 'live_data',
            'tools' => ['users_count', 'orders_summary'],
            'use_rag' => true,
            'rag_query' => 'revenue rules',
            'rag_limit' => 5,
            'use_memory' => true,
            'memory_query' => 'previous report',
            'memory_limit' => 4,
            'history_limit' => 10,
            'needs_clarification' => true,
            'clarification_question' => 'Which orders?',
            'metadata' => ['key' => 'value'],
            'planner' => 'llm',
        ];

        $plan = ExecutionPlan::fromArray($data);

        $this->assertSame('live_data', $plan->intent);
        $this->assertSame(['users_count', 'orders_summary'], $plan->tools);
        $this->assertTrue($plan->useRag);
        $this->assertSame('revenue rules', $plan->ragQuery);
        $this->assertSame(5, $plan->ragLimit);
        $this->assertTrue($plan->useMemory);
        $this->assertSame('previous report', $plan->memoryQuery);
        $this->assertSame(4, $plan->memoryLimit);
        $this->assertSame(10, $plan->historyLimit);
        $this->assertTrue($plan->needsClarification);
        $this->assertSame('Which orders?', $plan->clarificationQuestion);
        $this->assertSame(['key' => 'value'], $plan->metadata);
        $this->assertSame('llm', $plan->planner);
    }

    public function test_from_array_uses_defaults_for_missing_keys(): void
    {
        $plan = ExecutionPlan::fromArray(['intent' => 'knowledge']);

        $this->assertSame('knowledge', $plan->intent);
        $this->assertEmpty($plan->tools);
        $this->assertFalse($plan->useRag);
        $this->assertSame(3, $plan->ragLimit);
        $this->assertFalse($plan->useMemory);
        $this->assertSame(6, $plan->historyLimit);
    }

    public function test_to_array_round_trips(): void
    {
        $original = new ExecutionPlan;
        $original->intent = 'mixed';
        $original->tools = ['tool_a'];
        $original->useRag = true;
        $original->ragQuery = 'test query';
        $original->useMemory = false;
        $original->historyLimit = 8;
        $original->planner = 'llm';

        $array = $original->toArray();
        $restored = ExecutionPlan::fromArray($array);

        $this->assertSame($original->intent, $restored->intent);
        $this->assertSame($original->tools, $restored->tools);
        $this->assertSame($original->useRag, $restored->useRag);
        $this->assertSame($original->ragQuery, $restored->ragQuery);
        $this->assertSame($original->historyLimit, $restored->historyLimit);
        $this->assertSame($original->planner, $restored->planner);
    }

    public function test_is_simple_live_data(): void
    {
        $plan = new ExecutionPlan;
        $plan->intent = 'live_data';

        $this->assertTrue($plan->isSimpleLiveData());

        $plan->intent = 'direct';
        $this->assertTrue($plan->isSimpleLiveData());

        $plan->intent = 'mixed';
        $this->assertFalse($plan->isSimpleLiveData());
    }

    public function test_is_knowledge_request(): void
    {
        $plan = new ExecutionPlan;
        $plan->useRag = true;
        $this->assertTrue($plan->isKnowledgeRequest());

        $plan2 = new ExecutionPlan;
        $plan2->intent = 'knowledge';
        $this->assertTrue($plan2->isKnowledgeRequest());
    }

    public function test_is_memory_request(): void
    {
        $plan = new ExecutionPlan;
        $plan->useMemory = true;
        $this->assertTrue($plan->isMemoryRequest());

        $plan2 = new ExecutionPlan;
        $plan2->intent = 'memory';
        $this->assertTrue($plan2->isMemoryRequest());
    }

    public function test_requires_tools(): void
    {
        $plan = new ExecutionPlan;
        $plan->tools = ['users_count'];
        $this->assertTrue($plan->requiresTools());

        $plan2 = new ExecutionPlan;
        $plan2->intent = 'live_data';
        $this->assertTrue($plan2->requiresTools());

        $plan3 = new ExecutionPlan;
        $plan3->intent = 'knowledge';
        $this->assertFalse($plan3->requiresTools());
    }

    public function test_needs_llm_call(): void
    {
        $plan = new ExecutionPlan;
        $this->assertTrue($plan->needsLlmCall());

        $plan->needsClarification = true;
        $plan->clarificationQuestion = 'What do you mean?';
        $this->assertFalse($plan->needsLlmCall());
    }
}
