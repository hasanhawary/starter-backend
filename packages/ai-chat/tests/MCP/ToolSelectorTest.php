<?php

namespace AiChat\Tests\MCP;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolRegistry;
use AiChat\MCP\ToolResult;
use AiChat\MCP\ToolSelector;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Policies\ChatContext;
use Mockery as m;
use Tests\TestCase;

class SelectableTool implements ToolInterface
{
    public function __construct(
        private string $name,
        private string $description = '',
        private array $tags = [],
        private array $keywords = [],
        private bool $authorized = true,
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
        return $this->authorized;
    }

    public function execute(array $arguments, ChatContext $context): ToolResult
    {
        return ToolResult::success([]);
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function keywords(): array
    {
        return $this->keywords;
    }
}

class ToolSelectorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function createSelector(array $tools = []): ToolSelector
    {
        $registry = m::mock(ToolRegistry::class);

        foreach ($tools as $tool) {
            $registry->shouldReceive('get')->with($tool->name())->andReturn($tool);
        }
        $registry->shouldReceive('get')->andReturnNull();

        return new ToolSelector($registry);
    }

    public function test_selects_tools_from_plan(): void
    {
        $usersTool = new SelectableTool('users_count', 'Count users', ['users']);
        $ordersTool = new SelectableTool('orders_summary', 'Order summary', ['orders']);
        $selector = $this->createSelector([$usersTool, $ordersTool]);

        $plan = new ExecutionPlan;
        $plan->intent = 'live_data';
        $plan->tools = ['users_count', 'orders_summary'];

        $context = new ChatContext(action: 'chat', agent: null, user: null, payload: []);
        $selected = $selector->select($plan, $context);

        $this->assertArrayHasKey('users_count', $selected);
        $this->assertArrayHasKey('orders_summary', $selected);
        $this->assertCount(2, $selected);
    }

    public function test_filters_unauthorized_tools(): void
    {
        $allowedTool = new SelectableTool('public_tool', 'Public tool', [], [], true);
        $blockedTool = new SelectableTool('secret_tool', 'Secret tool', [], [], false);
        $selector = $this->createSelector([$allowedTool, $blockedTool]);

        $plan = new ExecutionPlan;
        $plan->tools = ['public_tool', 'secret_tool'];

        $context = new ChatContext(action: 'chat', agent: null, user: null, payload: []);
        $selected = $selector->select($plan, $context);

        $this->assertArrayHasKey('public_tool', $selected);
        $this->assertArrayNotHasKey('secret_tool', $selected);
    }

    public function test_returns_empty_when_plan_requires_no_tools(): void
    {
        $selector = $this->createSelector();

        $plan = new ExecutionPlan;
        $plan->intent = 'knowledge';
        $plan->useRag = true;

        $context = new ChatContext(action: 'chat', agent: null, user: null, payload: []);
        $selected = $selector->select($plan, $context);

        $this->assertEmpty($selected);
    }

    public function test_respects_max_tools_limit(): void
    {
        $tools = [];
        for ($i = 1; $i <= 10; $i++) {
            $tools[] = new SelectableTool("tool_{$i}", "Tool {$i}");
        }
        $selector = $this->createSelector($tools);

        $plan = new ExecutionPlan;
        $plan->tools = array_map(fn ($t) => $t->name(), $tools);

        $context = new ChatContext(action: 'chat', agent: null, user: null, payload: []);
        $selected = $selector->select($plan, $context);

        $this->assertLessThanOrEqual(5, count($selected));
    }

    public function test_auto_selects_by_intent_tags(): void
    {
        $usersTool = new SelectableTool('users_list', 'List users', ['users']);
        $docsTool = new SelectableTool('search_docs', 'Search docs', ['docs']);
        $registry = m::mock(ToolRegistry::class);
        $registry->shouldReceive('get')->with('users_list')->andReturn($usersTool);
        $registry->shouldReceive('get')->with('search_docs')->andReturn($docsTool);
        $registry->shouldReceive('get')->andReturnNull();
        $registry->shouldReceive('all')->andReturn([
            'users_list' => $usersTool,
            'search_docs' => $docsTool,
        ]);

        $selector = new ToolSelector($registry);

        $plan = new ExecutionPlan;
        $plan->intent = 'live_data';

        $context = new ChatContext(action: 'chat', agent: null, user: null, payload: []);
        $selected = $selector->select($plan, $context);

        $this->assertArrayHasKey('users_list', $selected);
    }
}
