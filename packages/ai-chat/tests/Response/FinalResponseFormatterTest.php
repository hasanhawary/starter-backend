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
}
