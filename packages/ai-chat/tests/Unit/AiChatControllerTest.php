<?php

namespace AiChat\Tests\Unit;

use AiChat\Chat\ConversationManager;
use AiChat\Chat\MessageManager;
use AiChat\Chat\StreamManager;
use AiChat\Http\Controllers\AiChatController;
use AiChat\Pipeline\ChatPipeline;
use AiChat\Response\FinalResponseFormatter;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use ReflectionMethod;
use Tests\TestCase;

class AiChatControllerTest extends TestCase
{
    public function test_extract_stream_chunk_prefers_delta_values(): void
    {
        $controller = new AiChatController(
            $this->mock(ConversationManager::class),
            $this->mock(MessageManager::class),
            $this->mock(ChatPipeline::class),
            $this->mock(StreamManager::class),
            app(FinalResponseFormatter::class),
        );

        $method = new ReflectionMethod($controller, 'extractStreamChunk');
        $method->setAccessible(true);

        $delta = new TextDelta('event-1', 'message-1', 'Hello ', time());
        $text = new class
        {
            public string $text = 'world';
        };
        $end = new StreamEnd('event-2', 'stop', new Usage, time());

        $this->assertSame('Hello ', $method->invoke($controller, $delta));
        $this->assertSame('world', $method->invoke($controller, $text));
        $this->assertSame('', $method->invoke($controller, $end));
    }
}
