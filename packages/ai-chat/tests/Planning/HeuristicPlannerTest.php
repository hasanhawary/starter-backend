<?php

namespace AiChat\Tests\Planning;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolRegistry;
use AiChat\MCP\ToolResult;
use AiChat\Planning\HeuristicPlanner;
use AiChat\Policies\ChatContext;
use Mockery as m;
use Tests\TestCase;

class FakeTool implements ToolInterface
{
    public function __construct(
        private string $name,
        private string $description = '',
        private array $keywords = [],
        private array $tags = [],
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function schema(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function authorize(ChatContext $context): bool
    {
        return true;
    }

    public function execute(array $arguments, ChatContext $context): ToolResult
    {
        return ToolResult::success([]);
    }

    public function keywords(): array
    {
        return $this->keywords;
    }

    public function tags(): array
    {
        return $this->tags;
    }
}

class HeuristicPlannerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    protected function createPlanner(array $tools = []): HeuristicPlanner
    {
        $registry = m::mock(ToolRegistry::class);

        foreach ($tools as $tool) {
            $registry->shouldReceive('get')->with($tool->name())->andReturn($tool);
            $registry->shouldReceive('all')->andReturn(array_combine(
                array_map(fn ($t) => $t->name(), $tools),
                $tools,
            ));
        }

        if (empty($tools)) {
            $registry->shouldReceive('all')->andReturn([]);
        }

        return new HeuristicPlanner($registry);
    }

    public function test_count_question_returns_live_data_plan(): void
    {
        $usersTool = new FakeTool('users_count', 'Returns total users count', ['users', 'user count'], ['users', 'analytics']);
        $planner = $this->createPlanner([$usersTool]);

        $plan = $planner->plan('How many users do we have?');

        $this->assertSame('live_data', $plan->intent);
        $this->assertContains('users_count', $plan->tools);
        $this->assertFalse($plan->useRag);
        $this->assertFalse($plan->useMemory);
        $this->assertSame('heuristic', $plan->planner);
    }

    public function test_arabic_count_question(): void
    {
        $usersTool = new FakeTool('users_count', 'Returns total users count', ['users', 'user count', 'عدد المستخدمين'], ['users']);
        $planner = $this->createPlanner([$usersTool]);

        $plan = $planner->plan('كام user عندي؟');

        $this->assertTrue(in_array($plan->intent, ['live_data', 'direct']));
    }

    public function test_knowledge_question_returns_rag_plan(): void
    {
        $planner = $this->createPlanner();

        $plan = $planner->plan('What are the refund rules?');

        $this->assertTrue($plan->useRag);
        $this->assertSame('knowledge', $plan->intent);
        $this->assertNotNull($plan->ragQuery);
    }

    public function test_memory_question_returns_memory_plan(): void
    {
        $planner = $this->createPlanner();

        $plan = $planner->plan('Continue the report we discussed before');

        $this->assertTrue($plan->useMemory);
        $this->assertSame('memory', $plan->intent);
        $this->assertNotNull($plan->memoryQuery);
    }

    public function test_complex_analytics_returns_mixed_plan(): void
    {
        $ordersTool = new FakeTool('orders_summary', 'Returns orders summary', [], ['orders', 'analytics']);
        $revenueTool = new FakeTool('revenue_summary', 'Returns revenue data', [], ['analytics']);
        $planner = $this->createPlanner([$ordersTool, $revenueTool]);

        $plan = $planner->plan('Compare this month orders with last month');

        $this->assertSame('mixed', $plan->intent);
        $this->assertTrue($plan->useRag);
        $this->assertNotEmpty($plan->tools);
    }

    public function test_direct_question_returns_direct_plan(): void
    {
        $planner = $this->createPlanner();

        $plan = $planner->plan('Hello, how are you?');

        $this->assertSame('direct', $plan->intent);
        $this->assertFalse($plan->useRag);
        $this->assertFalse($plan->useMemory);
        $this->assertEmpty($plan->tools);
    }

    public function test_can_handle_recognizes_patterns(): void
    {
        $tool = new FakeTool('test_tool', 'A test tool for testing');
        $planner = $this->createPlanner([$tool]);

        $this->assertTrue($planner->canHandle('How many users?'));
        $this->assertTrue($planner->canHandle('Explain the policy'));
        $this->assertTrue($planner->canHandle('Continue our discussion'));
        $this->assertTrue($planner->canHandle('latest users'));
    }

    public function test_can_handle_unknown_message(): void
    {
        $planner = $this->createPlanner();

        $result = $planner->canHandle('xyzabc123 unknown thing');

        $this->assertFalse($result);
    }

    public function test_tool_matching_by_name(): void
    {
        $tool = new FakeTool('users_list', 'List all users', ['users list']);
        $planner = $this->createPlanner([$tool]);

        $plan = $planner->plan('Show me the users list');

        $this->assertContains('users_list', $plan->tools);
    }

    public function test_tool_matching_by_keyword(): void
    {
        $tool = new FakeTool('orders_stats', 'Order statistics', ['عدد الطلبات', 'orders count'], ['orders']);
        $planner = $this->createPlanner([$tool]);

        $plan = $planner->plan('عدد الطلبات النهارده؟');

        $this->assertContains('orders_stats', $plan->tools);
    }

    public function test_tool_matching_by_description(): void
    {
        $tool = new FakeTool('customer_ranking', 'Find customers with highest number of orders', [], ['customers']);
        $planner = $this->createPlanner([$tool]);

        $plan = $planner->plan('Who has the highest number of orders?');

        $this->assertContains('customer_ranking', $plan->tools);
    }

    public function test_max_tools_limit_respected(): void
    {
        $tools = [];
        for ($i = 1; $i <= 10; $i++) {
            $tools[] = new FakeTool("tool_{$i}", "Tool {$i} for data analysis", ["keyword_{$i}"], ['analytics']);
        }
        $planner = $this->createPlanner($tools);

        $plan = $planner->plan('Show me analytics and data for everything');

        $this->assertLessThanOrEqual(5, count($plan->tools));
    }

    public function test_project_structure_intent(): void
    {
        $planner = $this->createPlanner();

        $plan = $planner->plan('Where is order status updated?');

        $this->assertTrue(in_array($plan->intent, ['project_structure', 'knowledge']));
    }
}
