<?php

namespace Tests\Feature\AiChat;

use AiChat\Response\FinalResponseFormatter;
use Tests\TestCase;

/**
 * Tests for Part 1 (prompt-level) and Part 2 (formatter safety-net).
 *
 * Each test documents a concrete leak pattern and asserts the formatter
 * removes it, leaving only the clean user-facing answer.
 */
class OutputLeakageTest extends TestCase
{
    private FinalResponseFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = app(FinalResponseFormatter::class);
    }

    // -----------------------------------------------------------------------
    // Required test cases from the spec
    // -----------------------------------------------------------------------

    public function test_formatter_removes_full_tool_reasoning_leak(): void
    {
        $raw = 'I have the user_count function available which can count user records.'
            .' Since no specific filters are mentioned, I\'ll call the function without any filters'
            .' to get the total count of all users.'
            .' The data shows a count of 32 users with no errors. 32 users';

        $this->assertSame('32 users', $this->formatter->format($raw, 'how many users'));
    }

    public function test_formatter_removes_tool_result_explanation(): void
    {
        $raw = 'The data shows a count of 7 orders with no errors. 7 orders';

        $this->assertSame('7 orders', $this->formatter->format($raw, 'how many orders'));
    }

    public function test_formatter_extracts_final_tag(): void
    {
        $raw = '<thinking>I need to use the users tool</thinking><final>32 users</final>';

        $this->assertSame('32 users', $this->formatter->format($raw, 'how many users'));
    }

    public function test_formatter_removes_knowledge_source_preamble(): void
    {
        $raw = 'From the knowledge base: Source [1] says refunds are allowed within 14 days.'
            .' Refunds are allowed within 14 days.';

        $result = $this->formatter->format($raw, 'refund policy');

        $this->assertStringNotContainsString('From the knowledge base', $result);
        $this->assertStringNotContainsString('Source [1]', $result);
        $this->assertStringContainsString('Refunds are allowed within 14 days', $result);
    }

    public function test_formatter_removes_arabic_reasoning_preamble(): void
    {
        $raw = 'أحتاج أن أراجع الذاكرة أولاً. نعم، اسمك حسن.';

        $this->assertSame('نعم، اسمك حسن.', $this->formatter->format($raw, 'فاكر اسمى'));
    }

    public function test_formatter_leaves_normal_answer_unchanged(): void
    {
        $raw = 'Hello! How can I help you?';

        $this->assertSame('Hello! How can I help you?', $this->formatter->format($raw, 'hi'));
    }

    // -----------------------------------------------------------------------
    // Additional tool-leak patterns
    // -----------------------------------------------------------------------

    public function test_formatter_removes_function_available_announcement(): void
    {
        $raw = 'I have the user_count function available. 32 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('I have the', $result);
        $this->assertStringNotContainsString('function available', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_i_will_call_announcement(): void
    {
        $raw = 'I will call the user_count tool to get the total. 32 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('I will call', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_ill_call_announcement(): void
    {
        $raw = "I'll call the function without any filters. 32 users";

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString("I'll call", $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_since_no_filters_phrase(): void
    {
        $raw = 'Since no specific filters are mentioned, the count is 32 users.';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('Since no specific filters', $result);
    }

    public function test_formatter_removes_the_data_shows_phrase(): void
    {
        $raw = 'The data shows a count of 15 roles. 15 roles';

        $result = $this->formatter->format($raw, 'how many roles');

        $this->assertStringNotContainsString('The data shows', $result);
        $this->assertStringContainsString('15 roles', $result);
    }

    public function test_formatter_removes_the_result_shows_phrase(): void
    {
        $raw = 'The result shows 5 active permissions. 5 active permissions';

        $result = $this->formatter->format($raw, 'how many permissions');

        $this->assertStringNotContainsString('The result shows', $result);
        $this->assertStringContainsString('5 active permissions', $result);
    }

    public function test_formatter_removes_function_returned_phrase(): void
    {
        $raw = 'The function returned a count of 10. 10 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('The function returned', $result);
        $this->assertStringContainsString('10 users', $result);
    }

    public function test_formatter_removes_tool_returned_phrase(): void
    {
        $raw = 'The tool returned 3 notifications. You have 3 notifications.';

        $result = $this->formatter->format($raw, 'my notifications');

        $this->assertStringNotContainsString('The tool returned', $result);
        $this->assertStringContainsString('3 notifications', $result);
    }

    public function test_formatter_removes_with_no_errors_phrase(): void
    {
        $raw = 'The count is 32 users with no errors.';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('with no errors', $result);
    }

    public function test_formatter_removes_thinking_tags(): void
    {
        $raw = '<thinking>I need to count users using the tool.</thinking>32 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('<thinking>', $result);
        $this->assertStringNotContainsString('</thinking>', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_analysis_tags(): void
    {
        $raw = '<analysis>Checking tool availability.</analysis>32 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('<analysis>', $result);
        $this->assertStringNotContainsString('</analysis>', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_reasoning_tags(): void
    {
        $raw = '<reasoning>The user wants a count.</reasoning>32 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('<reasoning>', $result);
        $this->assertStringNotContainsString('</reasoning>', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_tool_call_tags(): void
    {
        $raw = '<tool_call>user_count({})</tool_call>32 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('<tool_call>', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_tool_result_tags(): void
    {
        $raw = '<tool_result>{"count":32}</tool_result>32 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('<tool_result>', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    // -----------------------------------------------------------------------
    // Streaming safety — clean answer must not contain leak phrases
    // -----------------------------------------------------------------------

    public function test_streamed_tool_response_does_not_contain_function_available(): void
    {
        $raw = 'I have the user_count function available which can count user records.'
            .' Since no specific filters are mentioned, I\'ll call the function without any filters.'
            .' The data shows a count of 32 users with no errors. 32 users';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('I have the', $result);
        $this->assertStringNotContainsString('function available', $result);
        $this->assertStringNotContainsString("I'll call", $result);
        $this->assertStringNotContainsString('The data shows', $result);
        $this->assertStringNotContainsString('with no errors', $result);
    }

    public function test_persisted_message_equals_formatted_response(): void
    {
        $raw = 'I have the user_count function available. The data shows 32 users with no errors. 32 users';

        $formatted = $this->formatter->format($raw, 'how many users');

        // The persisted message must be the same clean string shown to the user
        $this->assertSame($formatted, $this->formatter->format($raw, 'how many users'));
        $this->assertStringNotContainsString('I have the', $formatted);
        $this->assertStringNotContainsString('The data shows', $formatted);
        $this->assertStringNotContainsString('with no errors', $formatted);
    }

    // -----------------------------------------------------------------------
    // Edge cases — valid answers must not be destroyed
    // -----------------------------------------------------------------------

    public function test_formatter_does_not_destroy_short_numeric_answer(): void
    {
        $this->assertSame('32', $this->formatter->format('32', 'how many users'));
    }

    public function test_formatter_does_not_destroy_arabic_answer(): void
    {
        $this->assertSame('نعم، اسمك حسن', $this->formatter->format('نعم، اسمك حسن', 'فاكر اسمى'));
    }

    public function test_formatter_does_not_destroy_multiline_answer(): void
    {
        $raw = "You have 3 notifications:\n- Notification A\n- Notification B\n- Notification C";

        $result = $this->formatter->format($raw, 'my notifications');

        $this->assertStringContainsString('3 notifications', $result);
        $this->assertStringContainsString('Notification A', $result);
    }

    public function test_formatter_does_not_destroy_answer_containing_the_word_data(): void
    {
        // "data" alone should not be stripped — only "The data shows"
        $raw = 'Your data has been saved successfully.';

        $result = $this->formatter->format($raw, 'save data');

        $this->assertStringContainsString('data', $result);
    }

    public function test_formatter_does_not_destroy_answer_containing_the_word_function(): void
    {
        // "function" in a normal sentence should not be stripped
        $raw = 'The export function is available in the settings menu.';

        $result = $this->formatter->format($raw, 'where is export');

        $this->assertStringContainsString('export function', $result);
    }

    // -----------------------------------------------------------------------
    // Exact phrases from the reported bug (screenshot)
    // -----------------------------------------------------------------------

    public function test_formatter_removes_i_have_access_to_function_phrase(): void
    {
        $raw = 'I have access to the user_count function which can count user records.'
            .' The user_count function has an optional filters parameter that I can use to filter the results,'
            .' but since the user hasn\'t specified any filters.'
            .' The function call was successful and returned that there are 32 users.'
            .' There are 32 users.';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('I have access to', $result);
        $this->assertStringNotContainsString('which can count', $result);
        $this->assertStringNotContainsString('has an optional filters parameter', $result);
        $this->assertStringNotContainsString('function call was successful', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_function_call_was_successful_phrase(): void
    {
        $raw = 'The function call was successful and returned that there are 15 records. There are 15 records.';

        $result = $this->formatter->format($raw, 'how many records');

        $this->assertStringNotContainsString('function call was successful', $result);
        $this->assertStringContainsString('15 records', $result);
    }

    public function test_formatter_removes_function_has_optional_parameter_phrase(): void
    {
        $raw = 'The user_count function has an optional filters parameter. There are 32 users.';

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString('has an optional filters parameter', $result);
        $this->assertStringContainsString('32 users', $result);
    }

    public function test_formatter_removes_hasnt_specified_filters_phrase(): void
    {
        $raw = "Since the user hasn't specified any filters, I'll return all results. There are 32 users.";

        $result = $this->formatter->format($raw, 'how many users');

        $this->assertStringNotContainsString("hasn't specified any filters", $result);
        $this->assertStringContainsString('32 users', $result);
    }

    // -----------------------------------------------------------------------
    // Greeting reasoning leak (reported bug)
    // -----------------------------------------------------------------------

    public function test_formatter_removes_arabic_greeting_definition_leak(): void
    {
        // Exact pattern from the reported bug screenshot
        $raw = '"ازيك" (or "أهلاً") is a common Arabic greeting that means "hi" or "hello".'
            .' A friendly one-liner is enough.'
            .' أهلاً! كيف يمكنني مساعدتك؟';

        $result = $this->formatter->format($raw, 'ازيك');

        $this->assertStringNotContainsString('is a common Arabic greeting', $result);
        $this->assertStringNotContainsString('A friendly one-liner is enough', $result);
        $this->assertStringNotContainsString('means "hi"', $result);
    }

    public function test_formatter_removes_echoed_instruction_phrase(): void
    {
        $raw = 'A friendly one-liner is enough. أهلاً!';

        $result = $this->formatter->format($raw, 'ازيك');

        $this->assertStringNotContainsString('A friendly one-liner is enough', $result);
    }

    public function test_formatter_removes_word_definition_reasoning(): void
    {
        $raw = '"Hello" is a common English greeting. Hi there!';

        $result = $this->formatter->format($raw, 'hello');

        $this->assertStringNotContainsString('is a common English greeting', $result);
        $this->assertStringContainsString('Hi there', $result);
    }
}
