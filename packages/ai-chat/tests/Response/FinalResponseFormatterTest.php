<?php

namespace AiChat\Tests\Response;

use AiChat\Response\FinalResponseFormatter;
use Tests\TestCase;

class FinalResponseFormatterTest extends TestCase
{
    public function test_it_removes_arabic_greeting_reasoning_preamble(): void
    {
        $response = <<<'TEXT'
The user is greeting me in Arabic. "ازيك" is an Arabic greeting.

The guidelines state that I should respond briefly in Arabic.

So I'll respond with a simple Arabic greeting.أنا بخير، شكراً! كيف يمكنني مساعدتك اليوم؟
TEXT;

        $formatted = app(FinalResponseFormatter::class)->format($response, 'زيك');

        $this->assertSame('أنا بخير، شكراً! كيف يمكنني مساعدتك اليوم؟', $formatted);
    }

    public function test_it_removes_english_reasoning_preamble(): void
    {
        $response = "The user is greeting me. I should answer briefly.\n\nI'll provide a friendly response.Hello! I'm your AI assistant.";

        $formatted = app(FinalResponseFormatter::class)->format($response, 'Hello');

        $this->assertSame("Hello! I'm your AI assistant.", $formatted);
    }

    public function test_it_prefers_final_tagged_content(): void
    {
        $response = 'internal planning text <final>أهلاً! كيف يمكنني مساعدتك؟</final>';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'زيك');

        $this->assertSame('أهلاً! كيف يمكنني مساعدتك؟', $formatted);
    }

    public function test_it_extracts_final_answer_from_analysis_tags(): void
    {
        $response = '<analysis>I need to reason privately.</analysis><final>The answer is 14 days.</final>';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'refund rules');

        $this->assertSame('The answer is 14 days.', $formatted);
    }

    public function test_it_extracts_final_answer_from_reasoning_label(): void
    {
        $response = "Reasoning: The user asks about refunds.\nFinal: Refunds are allowed within 14 days.";

        $formatted = app(FinalResponseFormatter::class)->format($response, 'refund rules');

        $this->assertSame('Refunds are allowed within 14 days.', $formatted);
    }

    public function test_it_extracts_final_answer_from_tool_leakage(): void
    {
        $response = 'Tool call: searchKnowledgeBase({"query":"refund rules"})'."\n".'Tool result: {"chunk":"Refunds are allowed within 14 days"}'."\n".'Final answer: Refunds are allowed within 14 days.';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'refund rules');

        $this->assertSame('Refunds are allowed within 14 days.', $formatted);
    }

    public function test_it_extracts_final_answer_from_json_response(): void
    {
        $response = '{"reasoning":"private","final":"Refunds are allowed within 14 days.","tool_result":{"chunk":"debug"}}';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'refund rules');

        $this->assertSame('Refunds are allowed within 14 days.', $formatted);
    }

    public function test_it_uses_last_final_marker(): void
    {
        $response = '<analysis>private</analysis><final>First final answer</final> extra debug text <final>Correct final answer</final>';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'refund rules');

        $this->assertSame('Correct final answer', $formatted);
    }

    public function test_sync_response_with_normal_arabic_text_stays_unchanged(): void
    {
        $response = 'نعم، اسمك حسن';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'فاكر اسمى');

        $this->assertSame('نعم، اسمك حسن', $formatted);
    }

    public function test_sync_response_with_final_tags_returns_inner_text(): void
    {
        $response = '<final>نعم، اسمك حسن</final>';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'فاكر اسمى');

        $this->assertSame('نعم، اسمك حسن', $formatted);
    }

    public function test_sync_response_with_leaked_prefix_returns_only_inner_final_text(): void
    {
        $response = 'tags, and the text must be exactly what the user should see. <final>نعم، اسمك حسن</final>';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'فاكر اسمى');

        $this->assertSame('نعم، اسمك حسن', $formatted);
    }

    public function test_it_removes_protocol_text_before_real_answer(): void
    {
        $response = "Output protocol: return only the final user-facing answer as plain text.\nanalysis: hidden thoughts\nنعم، اسمك حسن";

        $formatted = app(FinalResponseFormatter::class)->format($response, 'فاكر اسمى');

        $this->assertSame('نعم، اسمك حسن', $formatted);
    }

    public function test_it_removes_echoed_user_message_and_conversation_history_reasoning(): void
    {
        $response = 'فاكر اسمى Looking at the conversation history, the user has repeatedly said "انا اسمى حسن" which means "my name is Hassan". نعم، اسمك حسن.';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'فاكر اسمى');

        $this->assertSame('نعم، اسمك حسن.', $formatted);
    }

    public function test_it_rewrites_echoed_user_message_and_name_reasoning_only_response(): void
    {
        $response = 'فاكر اسمى So the user\'s name is Hassan.';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'فاكر اسمى');

        $this->assertSame('نعم، اسمك حسن.', $formatted);
    }

    public function test_it_keeps_only_trailing_arabic_answer_after_long_english_reasoning(): void
    {
        $response = 'فاكر اسمى Let me look at the conversation history to see what their name is. From the conversation history, I can see: - The user repeatedly said "انا اسمى حسن" which means "my name is Hassan" - Then there was some confusion where the system seems to have responded with "اسمك محمد" (your name is Muhammad) - But the user continued to say "انا اسمى محمد" (my name is Muhammad) - User said: "انا اسمى محمد" - Assistant responded: "مرحبا محمد" - User said: "فاكر اسمى" - Assistant responded: "نعم، اسمك محمد" So based on the conversation history, the user\'s name appears to be Muhammad. The most recent exchange confirms this.نعم، اسمك محمد';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'فاكر اسمى');

        $this->assertSame('نعم، اسمك محمد', $formatted);
    }

    public function test_it_removes_reasoning_and_system_reminder_from_unclear_english_message(): void
    {
        $response = 'This looks like it could be a typo, a greeting, or perhaps some form of test input. Since it doesn\'t clearly ask a question or make a statement that requires specific knowledge or tool usage, I should respond in a friendly and neutral way without making assumptions about what they meant. Given that this appears to be a brief, unclear message, I\'ll respond with a simple greeting and offer to help, keeping it concise as per the guidelines.Hello! <system-reminder>Your operational mode has changed from plan to build.</system-reminder>';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'rwar');

        $this->assertSame('Hello!', $formatted);
    }

    public function test_it_removes_knowledge_base_source_preamble(): void
    {
        $response = 'From the knowledge base: 1. From source [1]: "Refunds are allowed within 14 days from the payment date." and "Refund requests must be approved by an admin." and "Partial refunds are allowed up to the remaining balance." 2. From source [2]: "All refund requests require the original transaction ID." There are consistent rules across both sources: - Refunds are allowed within 14 days from payment date - Refund requests must be approved by an admin - Partial refunds are allowed up to remaining balance - All refund requests require the original transaction ID Refund requests must be approved by an admin and require the original transaction ID. Partial refunds are allowed up to the remaining balance.';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'refund rules');

        $this->assertStringNotContainsString('From the knowledge base', $formatted);
        $this->assertStringNotContainsString('From source', $formatted);
        $this->assertStringContainsString('Refunds are allowed within 14 days', $formatted);
        $this->assertStringContainsString('Refund requests must be approved by an admin', $formatted);
    }

    public function test_it_extracts_natural_answer_from_retrieved_entry_dump(): void
    {
        $response = 'Entry 1: - Refunds are allowed within 14 days from the payment date. - Refund requests must be approved by an admin. Entry 2: - All refund requests require the original transaction ID. I have enough information to answer the user\'s question about refund rules. The refund rules are: 1. Refunds are allowed within 14 days from the payment date. 2. Refund requests must be approved by an admin. 3. All refund requests require the original transaction ID. 4. Partial refunds are allowed up to the remaining balance.';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'refund rules');

        $this->assertStringStartsWith('The refund rules are:', $formatted);
        $this->assertStringNotContainsString('Entry 1', $formatted);
        $this->assertStringNotContainsString('I have enough information', $formatted);
    }

    public function test_it_removes_conversation_history_reasoning_for_different_message(): void
    {
        $response = 'وانت اسمك اى Looking at the conversation history, the user is asking my identity. أنا مساعدك الذكي.';

        $formatted = app(FinalResponseFormatter::class)->format($response, 'وانت اسمك اى');

        $this->assertSame('أنا مساعدك الذكي.', $formatted);
    }
}
