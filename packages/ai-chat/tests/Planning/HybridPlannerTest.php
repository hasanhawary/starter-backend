<?php

namespace AiChat\Tests\Planning;

use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Planning\HeuristicPlanner;
use AiChat\Planning\HybridPlanner;
use AiChat\Planning\LlmPlanner;
use Mockery as m;
use Tests\TestCase;

class HybridPlannerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_hybrid_mode_uses_heuristic_when_possible(): void
    {
        $heuristic = m::mock(HeuristicPlanner::class);
        $llm = m::mock(LlmPlanner::class);
        $registry = m::mock(ToolRegistry::class);
        $registry->shouldReceive('all')->andReturn([]);

        $expectedPlan = new ExecutionPlan;
        $expectedPlan->intent = 'live_data';
        $expectedPlan->tools = ['users_count'];

        $heuristic->shouldReceive('canHandle')->with('How many users?')->once()->andReturn(true);
        $heuristic->shouldReceive('plan')->with('How many users?', m::any(), null)->once()->andReturn($expectedPlan);

        config(['ai-chat.planning.mode' => 'hybrid']);

        $planner = new HybridPlanner($heuristic, $llm, $registry);
        $result = $planner->plan('How many users?');

        $this->assertSame('live_data', $result->intent);
        $this->assertSame('heuristic', $result->planner);
    }

    public function test_hybrid_mode_falls_back_to_llm_for_complex(): void
    {
        $heuristic = m::mock(HeuristicPlanner::class);
        $llm = m::mock(LlmPlanner::class);
        $registry = m::mock(ToolRegistry::class);
        $registry->shouldReceive('all')->andReturn([]);

        $llmPlan = new ExecutionPlan;
        $llmPlan->intent = 'mixed';
        $llmPlan->planner = 'llm';

        $heuristic->shouldReceive('canHandle')->with('complex ambiguous question')->once()->andReturn(false);
        $llm->shouldReceive('plan')->with('complex ambiguous question', [], null)->once()->andReturn($llmPlan);

        config(['ai-chat.planning.mode' => 'hybrid']);

        $planner = new HybridPlanner($heuristic, $llm, $registry);
        $result = $planner->plan('complex ambiguous question');

        $this->assertSame('mixed', $result->intent);
        $this->assertSame('llm', $result->planner);
    }

    public function test_heuristic_only_mode(): void
    {
        $expectedPlan = new ExecutionPlan;
        $expectedPlan->intent = 'direct';

        $heuristic = m::mock(HeuristicPlanner::class);
        $heuristic->shouldReceive('plan')->once()->andReturn($expectedPlan);

        $llm = m::mock(LlmPlanner::class);
        $registry = m::mock(ToolRegistry::class);
        $registry->shouldReceive('all')->andReturn([]);

        config(['ai-chat.planning.mode' => 'heuristic']);

        $planner = new HybridPlanner($heuristic, $llm, $registry);
        $result = $planner->plan('any message');

        $this->assertSame('direct', $result->intent);
    }

    public function test_llm_only_mode(): void
    {
        $expectedPlan = new ExecutionPlan;
        $expectedPlan->intent = 'knowledge';
        $expectedPlan->planner = 'llm';

        $llm = m::mock(LlmPlanner::class);
        $llm->shouldReceive('plan')->once()->andReturn($expectedPlan);

        $heuristic = m::mock(HeuristicPlanner::class);
        $registry = m::mock(ToolRegistry::class);
        $registry->shouldReceive('all')->andReturn([]);

        config(['ai-chat.planning.mode' => 'llm']);

        $planner = new HybridPlanner($heuristic, $llm, $registry);
        $result = $planner->plan('any message');

        $this->assertSame('knowledge', $result->intent);
    }

    public function test_llm_mode_falls_back_to_heuristic_on_null(): void
    {
        $fallbackPlan = new ExecutionPlan;
        $fallbackPlan->intent = 'direct';
        $fallbackPlan->planner = 'heuristic';

        $llm = m::mock(LlmPlanner::class);
        $llm->shouldReceive('plan')->once()->andReturn(null);

        $heuristic = m::mock(HeuristicPlanner::class);
        $heuristic->shouldReceive('plan')->once()->andReturn($fallbackPlan);

        $registry = m::mock(ToolRegistry::class);
        $registry->shouldReceive('all')->andReturn([]);

        config(['ai-chat.planning.mode' => 'llm']);

        $planner = new HybridPlanner($heuristic, $llm, $registry);
        $result = $planner->plan('test message');

        $this->assertSame('direct', $result->intent);
    }

    public function test_validate_sanitizes_plan(): void
    {
        $invalidPlan = new ExecutionPlan;
        $invalidPlan->intent = 'totally_invalid';
        $invalidPlan->tools = ['nonexistent_tool'];
        $invalidPlan->ragLimit = 50;
        $invalidPlan->historyLimit = 100;

        $heuristic = m::spy(HeuristicPlanner::class);
        $heuristic->shouldReceive('plan')->andReturn($invalidPlan);

        $llm = m::spy(LlmPlanner::class);
        $registry = m::spy(ToolRegistry::class);
        $registry->shouldReceive('all')->andReturn([]);

        $planner = new HybridPlanner($heuristic, $llm, $registry);
        $result = $planner->plan('test');

        $this->assertSame('direct', $result->intent);
        $this->assertEmpty($result->tools);
        $this->assertLessThanOrEqual(10, $result->ragLimit);
        $this->assertLessThanOrEqual(20, $result->historyLimit);
    }
}
