<?php

namespace Tests\Feature;

use AiChat\Agents\ChatAgent;
use AiChat\Chat\HistoryPolicy;
use AiChat\Chat\HistorySelector;
use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Planning\HeuristicPlanner;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistorySelectorTest extends TestCase
{
    protected HistorySelector $selector;

    protected HeuristicPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planner = new HeuristicPlanner(new ToolRegistry);
        $this->selector = new HistorySelector($this->planner);
    }

    // ========================================
    // HistoryPolicy tests
    // ========================================

    public function test_none_policy(): void
    {
        $policy = HistoryPolicy::none();

        $this->assertFalse($policy->useHistory);
        $this->assertEquals('none', $policy->mode);
        $this->assertEquals(0, $policy->limit);
    }

    public function test_recent_policy(): void
    {
        $policy = HistoryPolicy::recent(6);

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('recent', $policy->mode);
        $this->assertEquals(6, $policy->limit);
    }

    public function test_relevant_policy(): void
    {
        $policy = HistoryPolicy::relevant('user name', 3);

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('relevant', $policy->mode);
        $this->assertEquals('user name', $policy->query);
        $this->assertEquals(3, $policy->limit);
    }

    public function test_summary_policy(): void
    {
        $policy = HistoryPolicy::summary();

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('summary', $policy->mode);
        $this->assertEquals(20, $policy->limit);
    }

    public function test_policy_to_array_and_back(): void
    {
        $original = HistoryPolicy::relevant('user name', 5);
        $array = $original->toArray();
        $restored = HistoryPolicy::fromArray($array);

        $this->assertEquals($original->useHistory, $restored->useHistory);
        $this->assertEquals($original->mode, $restored->mode);
        $this->assertEquals($original->query, $restored->query);
        $this->assertEquals($original->limit, $restored->limit);
    }

    // ========================================
    // HistorySelector: mode detection
    // ========================================

    public function test_greeting_returns_none(): void
    {
        $plan = $this->planner->plan('ازيك');
        $policy = $this->selector->select($plan, 'ازيك');

        $this->assertFalse($policy->useHistory);
        $this->assertEquals('none', $policy->mode);
    }

    public function test_hello_returns_none(): void
    {
        $plan = $this->planner->plan('hello');
        $policy = $this->selector->select($plan, 'hello');

        $this->assertFalse($policy->useHistory);
        $this->assertEquals('none', $policy->mode);
    }

    public function test_shukran_returns_no_history(): void
    {
        $plan = $this->planner->plan('شكرا');
        $policy = $this->selector->select($plan, 'شكرا');

        $this->assertFalse($policy->useHistory);
        $this->assertEquals('none', $policy->mode);
    }

    public function test_identity_statement_returns_none(): void
    {
        $plan = $this->planner->plan('انا اسمي حسن وانت');
        $policy = $this->selector->select($plan, 'انا اسمي حسن وانت');

        $this->assertFalse($policy->useHistory);
    }

    public function test_whats_your_name_returns_none(): void
    {
        $plan = $this->planner->plan('اسمك ايه');
        $policy = $this->selector->select($plan, 'اسمك ايه');

        $this->assertEquals('none', $policy->mode);
    }

    public function test_recall_question_returns_relevant(): void
    {
        $plan = $this->planner->plan('اسمي ايه');
        $policy = $this->selector->select($plan, 'اسمي ايه');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('relevant', $policy->mode);
    }

    public function test_do_you_remember_my_name_returns_relevant(): void
    {
        $plan = $this->planner->plan('do you remember my name');
        $policy = $this->selector->select($plan, 'do you remember my name');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('relevant', $policy->mode);
    }

    public function test_faker_ismi_returns_relevant(): void
    {
        $plan = $this->planner->plan('فاكر اسمي');
        $policy = $this->selector->select($plan, 'فاكر اسمي');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('relevant', $policy->mode);
    }

    public function test_follow_up_continue_returns_recent(): void
    {
        $plan = $this->planner->plan('كمل');
        $policy = $this->selector->select($plan, 'كمل');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('recent', $policy->mode);
    }

    public function test_follow_up_explain_more_returns_recent(): void
    {
        $plan = $this->planner->plan('وضح اكتر');
        $policy = $this->selector->select($plan, 'وضح اكتر');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('recent', $policy->mode);
    }

    public function test_follow_up_why_returns_recent(): void
    {
        $plan = $this->planner->plan('ليه كده');
        $policy = $this->selector->select($plan, 'ليه كده');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('recent', $policy->mode);
    }

    public function test_summary_request_returns_summary(): void
    {
        $plan = $this->planner->plan('لخص المحادثة');
        $policy = $this->selector->select($plan, 'لخص المحادثة');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('summary', $policy->mode);
    }

    public function test_what_did_we_talk_about_returns_summary(): void
    {
        $plan = $this->planner->plan('what did we talk about');
        $policy = $this->selector->select($plan, 'what did we talk about');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('summary', $policy->mode);
    }

    public function test_knowledge_question_returns_recent(): void
    {
        $plan = $this->planner->plan('اشرحلي سياسة الاسترجاع');
        $policy = $this->selector->select($plan, 'اشرحلي سياسة الاسترجاع');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('recent', $policy->mode);
    }

    public function test_memory_reference_returns_relevant(): void
    {
        $plan = $this->planner->plan('كمل التقرير اللي قولتلك عليه قبل كده');
        $policy = $this->selector->select($plan, 'كمل التقرير اللي قولتلك عليه قبل كده');

        $this->assertTrue($policy->useHistory);
        $this->assertEquals('relevant', $policy->mode);
    }

    // ========================================
    // Filter messages
    // ========================================

    public function test_filter_none_returns_empty(): void
    {
        $messages = [['role' => 'user', 'content' => 'hello']];
        $result = $this->selector->filterMessages($messages, HistoryPolicy::none(), 'test');

        $this->assertEmpty($result);
    }

    public function test_filter_recent_takes_last_n(): void
    {
        $messages = [
            ['role' => 'user', 'content' => 'msg 1'],
            ['role' => 'assistant', 'content' => 'resp 1'],
            ['role' => 'user', 'content' => 'msg 2'],
            ['role' => 'assistant', 'content' => 'resp 2'],
            ['role' => 'user', 'content' => 'msg 3'],
        ];

        $result = $this->selector->filterMessages($messages, HistoryPolicy::recent(2), 'test');

        $this->assertCount(2, $result);
    }

    public function test_filter_relevant_filters_by_query(): void
    {
        $messages = [
            ['role' => 'user', 'content' => 'How many users?'],
            ['role' => 'assistant', 'content' => 'There are 5 users'],
            ['role' => 'user', 'content' => 'My name is Hassan'],
            ['role' => 'assistant', 'content' => 'Nice to meet you Hassan'],
            ['role' => 'user', 'content' => 'What is weather?'],
        ];

        $result = $this->selector->filterMessages($messages, HistoryPolicy::relevant('user name', 10), 'what is my name');

        $contents = array_map(fn (array $m) => $m['content'], $result);

        $this->assertContains('My name is Hassan', $contents);
    }

    // ========================================
    // History labels
    // ========================================

    public function test_history_label_none_is_empty(): void
    {
        $label = $this->selector->buildHistoryLabel(HistoryPolicy::none());

        $this->assertEmpty($label);
    }

    public function test_history_label_recent_is_empty(): void
    {
        $label = $this->selector->buildHistoryLabel(HistoryPolicy::recent(6));

        $this->assertEmpty($label);
    }

    public function test_history_label_relevant_contains_instruction(): void
    {
        $label = $this->selector->buildHistoryLabel(HistoryPolicy::relevant('user name', 3));

        $this->assertNotEmpty($label);
        $this->assertStringContainsString('Do not re-answer', $label);
    }

    public function test_history_label_summary_contains_instruction(): void
    {
        $label = $this->selector->buildHistoryLabel(HistoryPolicy::summary());

        $this->assertNotEmpty($label);
        $this->assertStringContainsString('Do not re-answer', $label);
    }

    // ========================================
    // ChatAgent integration with history
    // ========================================

    public function test_chat_agent_with_none_policy_returns_no_messages(): void
    {
        $agent = new ChatAgent('You are helpful.');
        $agent->withHistoryPolicy(HistoryPolicy::none());

        $messages = $agent->messages();

        $this->assertEmpty($messages);
    }

    public function test_chat_agent_with_recent_policy_without_conversation_returns_empty(): void
    {
        $agent = new ChatAgent('You are helpful.');
        $agent->withHistoryPolicy(HistoryPolicy::recent(2));

        $messages = $agent->messages();

        $this->assertEmpty($messages);
    }

    // ========================================
    // ExecutionPlan has historyMode
    // ========================================

    public function test_execution_plan_has_history_mode_field(): void
    {
        $plan = new ExecutionPlan;

        $this->assertEquals('recent', $plan->historyMode);
    }

    public function test_execution_plan_to_array_includes_history_mode(): void
    {
        $plan = new ExecutionPlan;
        $plan->historyMode = 'none';

        $array = $plan->toArray();

        $this->assertArrayHasKey('history_mode', $array);
        $this->assertEquals('none', $array['history_mode']);
    }

    public function test_execution_plan_from_array_reads_history_mode(): void
    {
        $plan = ExecutionPlan::fromArray([
            'intent' => 'direct',
            'history_mode' => 'none',
        ]);

        $this->assertEquals('none', $plan->historyMode);
    }

    // ========================================
    // Data providers for dialect greetings
    // ========================================

    #[DataProvider('noneHistoryProvider')]
    public function test_none_history_for_various_direct_messages(string $message): void
    {
        $plan = $this->planner->plan($message);
        $policy = $this->selector->select($plan, $message);

        $this->assertFalse($policy->useHistory, "Message '{$message}' should have no history");
    }

    public static function noneHistoryProvider(): array
    {
        return [
            'Egyptian greeting' => ['ازيك'],
            'Saudi greeting' => ['هلا والله'],
            'English greeting' => ['hello'],
            'Identity statement' => ['انا اسمي حسن وانت'],
            'Ask your name' => ['اسمك ايه'],
        ];
    }

    #[DataProvider('recallHistoryProvider')]
    public function test_relevant_history_for_recall_questions(string $message): void
    {
        $plan = $this->planner->plan($message);
        $policy = $this->selector->select($plan, $message);

        $this->assertTrue($policy->useHistory, "Message '{$message}' should have relevant history");
        $this->assertEquals('relevant', $policy->mode);
    }

    public static function recallHistoryProvider(): array
    {
        return [
            'What is my name' => ['what is my name'],
            'Do you remember my name' => ['do you remember my name'],
            'فاكر اسمي' => ['فاكر اسمي'],
            'اسمي ايه' => ['اسمي ايه'],
        ];
    }

    #[DataProvider('recentHistoryProvider')]
    public function test_recent_history_for_follow_ups(string $message): void
    {
        $plan = $this->planner->plan($message);
        $policy = $this->selector->select($plan, $message);

        $this->assertTrue($policy->useHistory, "Message '{$message}' should have recent history");
        $this->assertEquals('recent', $policy->mode);
    }

    public static function recentHistoryProvider(): array
    {
        return [
            'Continue' => ['كمل'],
            'Explain more' => ['وضح اكتر'],
            'Why' => ['ليه كده'],
        ];
    }
}
