<?php

namespace Tests\Feature;

use AiChat\Contracts\ToolInterface;
use AiChat\MCP\ToolRegistry;
use AiChat\MCP\ToolResult;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Planning\HeuristicPlanner;
use AiChat\Planning\ToolSearch\ArrayToolSearchIndex;
use AiChat\Policies\ChatContext;
use AiChat\Support\ArabicTextNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HeuristicPlannerTest extends TestCase
{
    protected HeuristicPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planner = new HeuristicPlanner(new ToolRegistry);
    }

    // ========================================
    // FEATURE 1: ArabicTextNormalizer
    // ========================================

    public function test_normalizer_removes_tashkeel(): void
    {
        $this->assertEquals('ازيك', ArabicTextNormalizer::normalize('إِزَيْكُ'));
    }

    public function test_normalizer_normalizes_alif_variants(): void
    {
        $this->assertEquals('ازيك', ArabicTextNormalizer::normalize('إزيك'));
        $this->assertEquals('ازيك', ArabicTextNormalizer::normalize('أزيك'));
        $this->assertEquals('ازيك', ArabicTextNormalizer::normalize('آزيك'));
    }

    public function test_normalizer_normalizes_ta_marbuta(): void
    {
        $this->assertEquals('سياسه', ArabicTextNormalizer::normalize('سياسة'));
    }

    public function test_normalizer_normalizes_alef_maqsura(): void
    {
        $result = ArabicTextNormalizer::normalize('مستشفى');
        $expected = ArabicTextNormalizer::normalize('مستشفي');
        $this->assertEquals($expected, $result);
    }

    public function test_normalizer_handles_mixed_arabic_english(): void
    {
        $this->assertEquals('ايه شروط refund', ArabicTextNormalizer::normalize('إيه شروط refund؟'));
    }

    public function test_normalizer_removes_tatweel(): void
    {
        $result = ArabicTextNormalizer::normalize('مـالـك');
        $this->assertFalse(str_contains($result, 'ـ'));
    }

    public function test_normalizer_lowercases_english(): void
    {
        $this->assertEquals('hello world', ArabicTextNormalizer::normalize('Hello World'));
    }

    public function test_normalizer_removes_punctuation(): void
    {
        $this->assertEquals('عامل ايه يا حسن', ArabicTextNormalizer::normalize('عامل إيه يا حسن؟'));
    }

    public function test_normalizer_reduces_multiple_spaces(): void
    {
        $this->assertEquals('ازيك يا صاحبي', ArabicTextNormalizer::normalize('ازيك  يا   صاحبي'));
    }

    public function test_normalizer_words_splits_correctly(): void
    {
        $words = ArabicTextNormalizer::words('عامل ايه يا معلم');
        $this->assertEquals(['عامل', 'ايه', 'يا', 'معلم'], $words);
    }

    public function test_normalizer_contains_any(): void
    {
        $this->assertTrue(ArabicTextNormalizer::containsAny('كام عدد الطلبات', ['كام', 'عدد']));
        $this->assertFalse(ArabicTextNormalizer::containsAny('مرحبا', ['كام', 'عدد']));
    }

    public function test_normalizer_exact_match(): void
    {
        $this->assertTrue(ArabicTextNormalizer::exactMatch('إزيك؟', 'ازيك'));
        $this->assertFalse(ArabicTextNormalizer::exactMatch('ازيك يا حسن', 'ازيك'));
    }

    public function test_normalizer_does_not_break_english(): void
    {
        $this->assertEquals('how many users', ArabicTextNormalizer::normalize('How many users?'));
    }

    public function test_normalizer_normalizes_yaa_with_hamza(): void
    {
        $result = ArabicTextNormalizer::normalize('متفائئ');
        $this->assertEquals('متفايي', $result);
    }

    // ========================================
    // FEATURE 2: Dialect Greetings
    // ========================================

    #[DataProvider('egyptianGreetingProvider')]
    public function test_egyptian_greetings_are_direct(string $greeting): void
    {
        $plan = $this->planner->plan($greeting);

        $this->assertEquals('direct', $plan->intent);
        $this->assertEquals(0, $plan->historyLimit);
        $this->assertFalse($plan->useRag);
        $this->assertFalse($plan->useMemory);
        $this->assertEmpty($plan->tools);
    }

    public static function egyptianGreetingProvider(): array
    {
        return [
            'ازيك' => ['ازيك'],
            'ازيك يا حسن' => ['ازيك يا حسن'],
            'عامل ايه' => ['عامل ايه'],
            'عامل ايه يا معلم' => ['عامل ايه يا معلم'],
            'اخبارك ايه' => ['اخبارك ايه'],
            'ايه الاخبار' => ['ايه الاخبار'],
        ];
    }

    #[DataProvider('saudiGreetingProvider')]
    public function test_saudi_greetings_are_direct(string $greeting): void
    {
        $plan = $this->planner->plan($greeting);

        $this->assertEquals('direct', $plan->intent);
        $this->assertEquals(0, $plan->historyLimit);
        $this->assertFalse($plan->useRag);
        $this->assertFalse($plan->useMemory);
        $this->assertEmpty($plan->tools);
    }

    public static function saudiGreetingProvider(): array
    {
        return [
            'هلا والله' => ['هلا والله'],
            'يا هلا وغلا' => ['يا هلا وغلا'],
            'وش اخبارك' => ['وش اخبارك'],
            'وش اخبارك اليوم' => ['وش اخبارك اليوم'],
            'كيفك يا غالي' => ['كيفك يا غالي'],
            'عساك طيب' => ['عساك طيب'],
            'حي الله' => ['حي الله'],
        ];
    }

    // ========================================
    // FEATURE 3: Memory Detection (NOT report alone)
    // ========================================

    #[DataProvider('memoryReferenceProvider')]
    public function test_memory_references_detected(string $message): void
    {
        $plan = $this->planner->plan($message);

        $this->assertEquals('memory', $plan->intent);
        $this->assertTrue($plan->useMemory);
    }

    public static function memoryReferenceProvider(): array
    {
        return [
            'كمل اللي فات' => ['كمل اللي فات'],
            'كمل التقرير اللي قولتلك عليه قبل كده' => ['كمل التقرير اللي قولتلك عليه قبل كده'],
            'continue what we discussed' => ['continue what we discussed'],
            'زي ما قلتلك' => ['زي ما قلتلك'],
        ];
    }

    #[DataProvider('notMemoryProvider')]
    public function test_report_alone_is_not_memory(string $message): void
    {
        $plan = $this->planner->plan($message);

        $this->assertNotEquals('memory', $plan->intent);
    }

    public static function notMemoryProvider(): array
    {
        return [
            'تقرير مبيعات' => ['اعمل تقرير مبيعات الشهر ده'],
            'report sales' => ['report sales this month'],
        ];
    }

    // ========================================
    // FEATURE 4: Live Data Detection
    // ========================================

    public function test_how_many_users_is_live_data(): void
    {
        $plan = $this->planner->plan('كام user عندي');

        $this->assertEquals('live_data', $plan->intent);
    }

    public function test_latest_users_is_live_data(): void
    {
        $plan = $this->planner->plan('هات اخر المستخدمين');

        $this->assertEquals('live_data', $plan->intent);
    }

    public function test_count_requests_is_live_data(): void
    {
        $plan = $this->planner->plan('كم عدد الطلبات');

        $this->assertEquals('live_data', $plan->intent);
    }

    // ========================================
    // FEATURE 5: Knowledge/RAG Detection
    // ========================================

    public function test_refund_policy_is_knowledge(): void
    {
        $plan = $this->planner->plan('اشرحلي سياسة الاسترجاع');

        $this->assertTrue($plan->isKnowledgeRequest());
    }

    public function test_what_are_refund_rules_is_knowledge(): void
    {
        $plan = $this->planner->plan('ايه شروط refund');

        $this->assertTrue($plan->isKnowledgeRequest());
    }

    public function test_how_does_refund_work_is_knowledge(): void
    {
        $plan = $this->planner->plan('how does refund work');

        $this->assertTrue($plan->isKnowledgeRequest());
    }

    // ========================================
    // FEATURE 6: Project Structure Detection
    // ========================================

    public function test_where_is_shipping_calculated_is_project_structure(): void
    {
        $plan = $this->planner->plan('فين بيتحسب سعر الشحن');

        $this->assertTrue($plan->isKnowledgeRequest());
    }

    public function test_which_service_creates_invoices_is_project_structure(): void
    {
        $plan = $this->planner->plan('which service creates invoices');

        $this->assertTrue($plan->isKnowledgeRequest());
    }

    // ========================================
    // FEATURE 7: Complex Analytics
    // ========================================

    public function test_compare_orders_is_mixed_analytics(): void
    {
        $plan = $this->planner->plan('قارن الطلبات الشهر ده بالشهر اللي فات');

        $this->assertEquals('mixed', $plan->intent);
    }

    public function test_compare_with_business_rules_is_mixed_with_rag(): void
    {
        $plan = $this->planner->plan('قارن الطلبات ووضح قواعد حساب revenue');

        $this->assertEquals('mixed', $plan->intent);
        $this->assertTrue($plan->useRag);
    }

    // ========================================
    // FEATURE 8: Confidence Scoring
    // ========================================

    public function test_greeting_has_high_confidence(): void
    {
        $plan = $this->planner->plan('ازيك');

        $this->assertArrayHasKey('confidence', $plan->metadata);
        $this->assertGreaterThanOrEqual(0.90, $plan->metadata['confidence']);
        $this->assertArrayHasKey('reason', $plan->metadata);
    }

    public function test_memory_has_high_confidence(): void
    {
        $plan = $this->planner->plan('كمل اللي فات');

        $this->assertArrayHasKey('confidence', $plan->metadata);
        $this->assertGreaterThanOrEqual(0.90, $plan->metadata['confidence']);
    }

    public function test_knowledge_has_confidence(): void
    {
        $plan = $this->planner->plan('اشرحلي سياسة الاسترجاع');

        $this->assertArrayHasKey('confidence', $plan->metadata);
        $this->assertGreaterThanOrEqual(0.80, $plan->metadata['confidence']);
    }

    public function test_direct_unknown_has_low_confidence(): void
    {
        $plan = $this->planner->plan('ممكن تساعدني');

        $this->assertArrayHasKey('confidence', $plan->metadata);
        $this->assertLessThanOrEqual(0.50, $plan->metadata['confidence']);
    }

    public function test_should_use_llm_for_low_confidence(): void
    {
        $plan = new ExecutionPlan;
        $plan->intent = 'direct';
        $plan->metadata['confidence'] = 0.30;

        $this->assertTrue($this->planner->shouldUseLlm($plan));
    }

    public function test_should_not_use_llm_for_high_confidence(): void
    {
        $plan = new ExecutionPlan;
        $plan->intent = 'direct';
        $plan->metadata['confidence'] = 0.95;

        $this->assertFalse($this->planner->shouldUseLlm($plan));
    }

    public function test_get_confidence_returns_metadata_value(): void
    {
        $plan = new ExecutionPlan;
        $plan->metadata['confidence'] = 0.85;

        $this->assertEquals(0.85, $this->planner->getConfidence($plan));
    }

    public function test_get_confidence_defaults_to_0_50(): void
    {
        $plan = new ExecutionPlan;

        $this->assertEquals(0.50, $this->planner->getConfidence($plan));
    }

    // ========================================
    // FEATURE 9: canHandle
    // ========================================

    public function test_can_handle_greeting(): void
    {
        $this->assertTrue($this->planner->canHandle('ازيك'));
    }

    public function test_can_handle_live_data(): void
    {
        $this->assertTrue($this->planner->canHandle('كام عدد المستخدمين'));
    }

    public function test_can_handle_knowledge(): void
    {
        $this->assertTrue($this->planner->canHandle('اشرحلي سياسة الاسترجاع'));
    }

    public function test_can_handle_memory(): void
    {
        $this->assertTrue($this->planner->canHandle('كمل اللي فات'));
    }

    public function test_can_handle_project_structure(): void
    {
        $this->assertTrue($this->planner->canHandle('فين بيتحسب سعر الشحن'));
    }

    // ========================================
    // FEATURE 10: Tool Search Index
    // ========================================

    public function test_array_tool_search_index_basic(): void
    {
        $index = new ArrayToolSearchIndex;

        $index->index([
            $this->makeFakeTool('user_count', 'Count total users', ['users', 'count', 'كام', 'عدد'], ['users', 'count']),
            $this->makeFakeTool('order_search', 'Search orders', ['orders', 'search', 'ابحث'], ['orders', 'search']),
        ]);

        $results = $index->search('كام عدد المستخدمين');

        $this->assertCount(1, $results);
        $this->assertEquals('user_count', $results[0]['name']);
    }

    public function test_array_tool_search_index_exact_name(): void
    {
        $index = new ArrayToolSearchIndex;

        $index->index([
            $this->makeFakeTool('user_count', 'Count total users'),
        ]);

        $results = $index->search('user_count');

        $this->assertCount(1, $results);
        $this->assertEquals(1.00, $results[0]['score']);
    }

    public function test_array_tool_search_index_keyword_match(): void
    {
        $index = new ArrayToolSearchIndex;

        $index->index([
            $this->makeFakeTool('user_count', 'Count total users', ['users', 'count', 'how many']),
        ]);

        $results = $index->search('how many users');

        $this->assertCount(1, $results);
        $this->assertGreaterThanOrEqual(0.85, $results[0]['score']);
    }

    public function test_array_tool_search_index_respects_limit(): void
    {
        $index = new ArrayToolSearchIndex;

        $index->index([
            $this->makeFakeTool('tool_a', 'A description', ['a']),
            $this->makeFakeTool('tool_b', 'B description', ['b']),
            $this->makeFakeTool('tool_c', 'C description', ['c']),
        ]);

        $results = $index->search('a b c', 2);

        $this->assertCount(2, $results);
    }

    public function test_array_tool_search_index_empty_for_no_match(): void
    {
        $index = new ArrayToolSearchIndex;

        $index->index([
            $this->makeFakeTool('user_count', 'Count total users'),
        ]);

        $results = $index->search('greeting hello');

        $this->assertEmpty($results);
    }

    // ========================================
    // FEATURE 11: Tool Matching with Threshold
    // ========================================

    public function test_tool_matching_with_high_threshold(): void
    {
        config(['ai-chat.planning.tool_match_threshold' => 0.90]);

        $registry = new ToolRegistry;
        $registry->register($this->makeFakeTool('user_count', 'Count total users'));

        $planner = new HeuristicPlanner($registry);
        $plan = $planner->plan('user_count');

        $this->assertNotEmpty($plan->tools);
        $this->assertEquals('user_count', $plan->tools[0]);
    }

    public function test_tool_matching_with_low_threshold(): void
    {
        config(['ai-chat.planning.tool_match_threshold' => 0.50]);

        $registry = new ToolRegistry;
        $registry->register($this->makeFakeTool('user_count', 'Count total users', ['users', 'how many']));

        $planner = new HeuristicPlanner($registry);
        $plan = $planner->plan('how many users');

        $this->assertNotEmpty($plan->tools);
    }

    // ========================================
    // FEATURE 12: Dialect Merging
    // ========================================

    public function test_config_greetings_are_merged(): void
    {
        config(['ai-chat-dialects.greetings.egyptian' => ['ازيك', 'عامل ايه']]);

        $planner = new HeuristicPlanner(new ToolRegistry);
        $plan = $planner->plan('ازيك');

        $this->assertEquals('direct', $plan->intent);
        $this->assertArrayHasKey('confidence', $plan->metadata);
    }

    // ========================================
    // FEATURE 13: Regression - greeting after context
    // ========================================

    public function test_greeting_after_knowledge_question(): void
    {
        $this->planner->plan('ما هي سياسة الاسترداد');
        $this->planner->plan('كم عدد المستخدمين');
        $this->planner->plan('كمل اللي فات');

        $plan = $this->planner->plan('ازيك');

        $this->assertEquals('direct', $plan->intent);
        $this->assertEmpty($plan->tools);
        $this->assertFalse($plan->useRag);
        $this->assertFalse($plan->useMemory);
        $this->assertEquals(0, $plan->historyLimit);
    }

    public function test_memory_after_instructions_still_works(): void
    {
        $this->planner->plan('احفظ اني عايز تقرير شهري');

        $plan = $this->planner->plan('كمل التقرير اللي قولتلك عليه قبل كده');

        $this->assertEquals('memory', $plan->intent);
        $this->assertTrue($plan->useMemory);
    }

    // ========================================
    // Helpers
    // ========================================

    protected function makeFakeTool(
        string $name,
        string $description,
        array $keywords = [],
        array $tags = [],
    ): ToolInterface {
        return new class($name, $description, $keywords, $tags) implements ToolInterface
        {
            public function __construct(
                protected string $name,
                protected string $description,
                protected array $keywords,
                protected array $tags,
            ) {}

            public function name(): string
            {
                return $this->name;
            }

            public function description(): string
            {
                return $this->description;
            }

            public function keywords(): array
            {
                return $this->keywords;
            }

            public function tags(): array
            {
                return $this->tags;
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
                return new ToolResult($this->name, 'ok');
            }
        };
    }
}
