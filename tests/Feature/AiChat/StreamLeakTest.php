<?php

namespace Tests\Feature\AiChat;

use AiChat\Contracts\LLMProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreamLeakTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeProviderWithLeak(string $responseText = 'I should respond with final answer. Hello!'): void
    {
        $this->app->singleton(LLMProviderInterface::class, fn () => new class($responseText) implements LLMProviderInterface
        {
            public function __construct(private string $text) {}

            public function send(array $messages, array $tools = [], array $options = []): array
            {
                return ['content' => $this->text, 'usage' => ['total_tokens' => 50]];
            }

            public function stream(array $messages, array $tools = [], array $options = []): \Generator
            {
                // First chunk includes internal reasoning, second chunk final answer
                yield ['content' => "I should respond with final answer.\n"];
                yield ['content' => "Hello!\n"];
            }

            public function name(): string
            {
                return 'fake';
            }
        });
    }

    public function test_streaming_filters_internal_reasoning(): void
    {
        $this->fakeProviderWithLeak();
        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hi',
            'session_id' => 'test-stream-123',
            'stream' => true,
        ]);
        $response->assertStatus(200);
        $content = $response->streamedContent();
        // Ensure internal phrase is not present
        $this->assertStringNotContainsString('I should respond', $content);
        $this->assertStringContainsString('Hello!', $content);
    }
}
