<?php

namespace AiChat\Tests\Support;

use AiChat\Support\TokenBudgetManager;
use Tests\TestCase;

class TokenBudgetManagerTest extends TestCase
{
    private TokenBudgetManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai-chat.context.max_context_tokens' => 8000,
            'ai-chat.context.system_prompt_budget' => 1500,
            'ai-chat.context.history_limit' => 6,
            'ai-chat.context.rag_limit' => 3,
            'ai-chat.context.memory_limit' => 3,
            'ai-chat.context.max_tools' => 5,
            'ai-chat.context.max_tool_result_items' => 10,
        ]);

        $this->manager = new TokenBudgetManager;
    }

    public function test_default_values(): void
    {
        $this->assertSame(8000, $this->manager->getMaxContextTokens());
        $this->assertSame(1500, $this->manager->getSystemPromptBudget());
        $this->assertSame(6, $this->manager->getHistoryLimit());
        $this->assertSame(3, $this->manager->getRagLimit());
        $this->assertSame(3, $this->manager->getMemoryLimit());
        $this->assertSame(5, $this->manager->getMaxTools());
        $this->assertSame(10, $this->manager->getMaxToolResultItems());
    }

    public function test_history_limit_uses_plan_value_when_lower(): void
    {
        $result = $this->manager->getHistoryLimit(4);

        $this->assertSame(4, $result);
    }

    public function test_history_limit_capped_by_config(): void
    {
        $result = $this->manager->getHistoryLimit(20);

        $this->assertSame(6, $result);
    }

    public function test_rag_limit_uses_plan_value(): void
    {
        $result = $this->manager->getRagLimit(2);

        $this->assertSame(2, $result);
    }

    public function test_rag_limit_capped_by_config(): void
    {
        $result = $this->manager->getRagLimit(10);

        $this->assertSame(3, $result);
    }

    public function test_memory_limit_uses_plan_value(): void
    {
        $result = $this->manager->getMemoryLimit(1);

        $this->assertSame(1, $result);
    }

    public function test_calculate_available_for_content(): void
    {
        $available = $this->manager->calculateAvailableForContent(1000, 500);

        $this->assertSame(6000, $available);
    }

    public function test_needs_trimming_with_small_messages(): void
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Hello'],
        ];

        $this->assertFalse($this->manager->needsTrimming($messages));
    }

    public function test_needs_trimming_with_large_messages(): void
    {
        $messages = [
            ['role' => 'system', 'content' => str_repeat('word ', 2000)],
            ['role' => 'user', 'content' => str_repeat('word ', 5000)],
        ];

        $this->assertTrue($this->manager->needsTrimming($messages));
    }

    public function test_trim_to_budget_keeps_within_limit(): void
    {
        $items = array_fill(0, 50, 'This is a medium sized item with enough words to count');

        $trimmed = $this->manager->trimToBudget($items, 100);

        $this->assertNotEmpty($trimmed);
        $this->assertLessThanOrEqual(count($items), count($trimmed));
        $this->assertGreaterThanOrEqual(1, count($trimmed));
    }

    public function test_priority_order_is_correct(): void
    {
        $order = $this->manager->getPriorityOrder();

        $this->assertSame('system', $order[0]);
        $this->assertContains('history', $order);
        $this->assertContains('rag', $order);
        $this->assertContains('memory', $order);
    }
}
