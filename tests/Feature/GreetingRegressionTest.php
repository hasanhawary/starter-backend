<?php

namespace Tests\Feature;

use AiChat\Agents\ChatAgent;
use AiChat\Chat\HistoryPolicy;
use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ChatPayload;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Pipeline\Steps\SendToProvider;
use AiChat\Planning\HeuristicPlanner;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GreetingRegressionTest extends TestCase
{
    protected HeuristicPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planner = new HeuristicPlanner(new ToolRegistry);
    }

    public function test_greeting_after_previous_questions_produces_direct_plan(): void
    {
        $plan = $this->planner->plan('ازيك');

        $this->assertEquals('direct', $plan->intent);
        $this->assertFalse($plan->useRag);
        $this->assertFalse($plan->useMemory);
        $this->assertEmpty($plan->tools);
        $this->assertEquals(0, $plan->historyLimit);
    }

    public function test_greeting_hello_produces_direct_plan(): void
    {
        $plan = $this->planner->plan('hello');

        $this->assertEquals('direct', $plan->intent);
        $this->assertEquals(0, $plan->historyLimit);
        $this->assertEmpty($plan->tools);
    }

    public function test_greeting_arabic_produces_direct_plan(): void
    {
        $plan = $this->planner->plan('عامل ايه');

        $this->assertEquals('direct', $plan->intent);
        $this->assertEquals(0, $plan->historyLimit);
        $this->assertEmpty($plan->tools);
    }

    public function test_memory_reference_triggers_memory_plan(): void
    {
        $plan = $this->planner->plan('كمل التقرير اللي قولتلك عليه قبل كده');

        $this->assertEquals('memory', $plan->intent);
        $this->assertTrue($plan->useMemory);
    }

    public function test_knowledge_question_still_produces_knowledge_plan(): void
    {
        $plan = $this->planner->plan('ما هي سياسة الاسترداد');

        $this->assertEquals('knowledge', $plan->intent);
        $this->assertTrue($plan->useRag);
    }

    public function test_direct_plan_does_not_require_tools(): void
    {
        $plan = new ExecutionPlan;
        $plan->intent = 'direct';

        $this->assertFalse($plan->requiresTools());
    }

    public function test_live_data_plan_requires_tools(): void
    {
        $plan = new ExecutionPlan;
        $plan->intent = 'live_data';

        $this->assertTrue($plan->requiresTools());
    }

    public function test_plan_with_explicit_tools_requires_tools(): void
    {
        $plan = new ExecutionPlan;
        $plan->intent = 'direct';
        $plan->tools = ['user_count'];

        $this->assertTrue($plan->requiresTools());
    }

    public function test_chat_agent_with_zero_history_limit_returns_no_messages(): void
    {
        $agent = new ChatAgent('You are a helpful assistant.');
        $agent->withHistoryLimit(0);

        $messages = $agent->messages();

        $this->assertEmpty($messages);
    }

    public function test_chat_agent_default_history_limit_is_100(): void
    {
        $agent = new ChatAgent('You are a helpful assistant.');

        $ref = new \ReflectionProperty($agent, 'historyLimit');
        $this->assertEquals(100, $ref->getValue($agent));
    }

    public function test_chat_agent_with_history_limit_updates_limit(): void
    {
        $agent = new ChatAgent('You are a helpful assistant.');
        $agent->withHistoryLimit(2);

        $ref = new \ReflectionProperty($agent, 'historyLimit');
        $this->assertEquals(2, $ref->getValue($agent));
    }

    public function test_system_prompt_for_greeting_includes_brief_instruction(): void
    {
        $agent = new ChatAgent('You are a helpful AI assistant.');
        $agent->withHistoryPolicy(HistoryPolicy::none());

        $instructions = (string) $agent->instructions();

        $this->assertStringContainsString('not asking about any previous conversation topic', $instructions);
        $this->assertStringContainsString('Reply naturally', $instructions);
        $this->assertStringContainsString('latest user message', $instructions);
    }

    public function test_system_prompt_for_knowledge_does_not_include_greeting_instruction(): void
    {
        $agent = new ChatAgent('You are a helpful AI assistant.');
        $plan = new ExecutionPlan;
        $plan->intent = 'knowledge';
        $plan->useRag = true;
        $agent->withExecutionPlan($plan);
        $agent->withCurrentMessage('ما هي سياسة الاسترداد');

        $instructions = (string) $agent->instructions();

        $this->assertStringNotContainsString('not asking about any previous conversation topic', $instructions);
    }

    public function test_system_prompt_for_memory_does_not_include_greeting_instruction(): void
    {
        $agent = new ChatAgent('You are a helpful AI assistant.');
        $plan = new ExecutionPlan;
        $plan->intent = 'memory';
        $plan->useMemory = true;
        $agent->withExecutionPlan($plan);
        $agent->withCurrentMessage('كمل التقرير');

        $instructions = (string) $agent->instructions();

        $this->assertStringNotContainsString('not asking about any previous conversation topic', $instructions);
    }

    #[DataProvider('greetingProvider')]
    public function test_all_greeting_patterns_produce_direct_plan(string $greeting): void
    {
        $plan = $this->planner->plan($greeting);

        $this->assertEquals('direct', $plan->intent);
        $this->assertEquals(0, $plan->historyLimit);
        $this->assertFalse($plan->useRag);
        $this->assertFalse($plan->useMemory);
        $this->assertEmpty($plan->tools);
    }

    public static function greetingProvider(): array
    {
        return [
            'Egyptian greeting' => ['ازيك'],
            'Egyptian greeting variant' => ['ازايك'],
            'Arabic hello' => ['اهلا'],
            'Arabic welcome' => ['مرحبا'],
            'Arabic peace' => ['سلام'],
            'Arabic morning' => ['صباح الخير'],
            'Arabic evening' => ['مساء الخير'],
            'How are you doing' => ['عامل ايه'],
            'How are you all' => ['عاملين ايه'],
            'How is your state' => ['كيف حالك'],
            'English hello' => ['hello'],
            'English hi' => ['hi'],
            'English hey' => ['hey'],
            'English good morning' => ['good morning'],
            'English good evening' => ['good evening'],
            'English sup' => ['sup'],
            'Hala' => ['هلا'],
            'Ya hala' => ['يا هلا'],
        ];
    }

    protected function invokeResolveSystemPrompt(
        SendToProvider $step,
        ChatPayload $payload,
    ): string {
        $ref = new \ReflectionMethod($step, 'resolveSystemPrompt');
        $ref->setAccessible(true);

        return $ref->invoke($step, $payload);
    }
}
