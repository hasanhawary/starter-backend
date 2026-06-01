<?php

namespace AiChat\Tests\Unit;

use AiChat\Chat\ConversationManager;
use AiChat\Chat\MessageManager;
use AiChat\Chat\StreamManager;
use AiChat\Http\Controllers\AiChatController;
use AiChat\Http\Requests\SendMessageRequest;
use AiChat\Models\AiChatConversation;
use AiChat\Pipeline\ChatPayload;
use AiChat\Pipeline\ChatPipeline;
use AiChat\Response\FinalResponseFormatter;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Mockery;
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

    public function test_streaming_sanitizer_does_not_emit_protocol_fragments(): void
    {
        $controller = $this->controller();
        $extract = new ReflectionMethod($controller, 'extractFinalVisibleStreamChunk');
        $extract->setAccessible(true);
        $formatter = app(FinalResponseFormatter::class);

        $finalBuffer = '';
        $insideFinal = false;
        $sanitizerBuffer = '';
        $emitted = '';
        $chunks = [
            'tags, and the text must be exactly what the user should see. <final>',
            'نعم، اسمك حسن',
            '</final>',
        ];

        foreach ($chunks as $chunk) {
            $visible = $extract->invokeArgs($controller, [$chunk, &$finalBuffer, &$insideFinal]);
            $emitted .= $formatter->sanitizeStreamDelta($visible, $sanitizerBuffer);
        }

        $emitted .= $formatter->sanitizeStreamDelta('', $sanitizerBuffer, flush: true);

        $this->assertSame('نعم، اسمك حسن', $emitted);
        $this->assertStringNotContainsString('<final>', $emitted);
        $this->assertStringNotContainsString('</final>', $emitted);
        $this->assertStringNotContainsString('tags, and the text must be exactly what the user should see', $emitted);
    }

    public function test_streaming_sanitizer_handles_protocol_text_split_across_chunks(): void
    {
        $controller = $this->controller();
        $extract = new ReflectionMethod($controller, 'extractFinalVisibleStreamChunk');
        $extract->setAccessible(true);
        $formatter = app(FinalResponseFormatter::class);

        $finalBuffer = '';
        $insideFinal = false;
        $sanitizerBuffer = '';
        $emitted = '';
        $chunks = [
            'ta',
            'gs, and the text must be exactly what the user should see. <fi',
            'nal>نعم، اسمك حسن</fi',
            'nal>',
        ];

        foreach ($chunks as $chunk) {
            $visible = $extract->invokeArgs($controller, [$chunk, &$finalBuffer, &$insideFinal]);
            $emitted .= $formatter->sanitizeStreamDelta($visible, $sanitizerBuffer);
        }

        $emitted .= $formatter->sanitizeStreamDelta('', $sanitizerBuffer, flush: true);

        $this->assertSame('نعم، اسمك حسن', $emitted);
        $this->assertStringNotContainsString('<final>', $emitted);
        $this->assertStringNotContainsString('</final>', $emitted);
        $this->assertStringNotContainsString('tags, and the text must be exactly what the user should see', $emitted);
    }

    public function test_text_end_flushes_buffered_sanitized_stream_delta(): void
    {
        $controller = $this->controller();
        $method = new ReflectionMethod($controller, 'formatStreamEvent');
        $method->setAccessible(true);
        $event = new TextEnd('event-2', 'message-1', time());

        $formatted = $method->invoke(
            $controller,
            $event,
            'نعم، اسمك حسن',
            '<final>نعم، اسمك حسن</final>',
            'فاكر اسمى',
            null,
            true,
        );

        $this->assertSame(['type' => 'text_delta', 'delta' => 'نعم، اسمك حسن'], json_decode($formatted, true));
    }

    public function test_process_sync_returns_sanitized_assistant_content(): void
    {
        $pipeline = Mockery::mock(ChatPipeline::class);
        $pipeline->shouldReceive('process')
            ->once()
            ->andReturnUsing(function (ChatPayload $payload): ChatPayload {
                $payload->conversation = new AiChatConversation(['id' => 'conversation-1']);
                $payload->response = 'tags, and the text must be exactly what the user should see. <final>نعم، اسمك حسن</final>';

                return $payload;
            });

        $controller = new AiChatController(
            $this->mock(ConversationManager::class),
            $this->mock(MessageManager::class),
            $pipeline,
            $this->mock(StreamManager::class),
            app(FinalResponseFormatter::class),
        );

        $request = SendMessageRequest::create('/api/ai-chat/messages', 'POST', [
            'message' => 'فاكر اسمى',
            'session_id' => 'session-12345',
        ]);
        $request->setContainer(app());
        $request->setValidator(Validator::make($request->all(), $request->rules()));

        $method = new ReflectionMethod($controller, 'processSync');
        $method->setAccessible(true);
        $response = $method->invoke($controller, $request);
        $data = $response->getData(true);

        $this->assertSame('نعم، اسمك حسن', $data['data']['message']['content']);
        $this->assertStringNotContainsString('<final>', $data['data']['message']['content']);
        $this->assertStringNotContainsString('tags, and the text must be exactly what the user should see', $data['data']['message']['content']);
    }

    public function test_stored_streaming_assistant_message_is_sanitized(): void
    {
        $messageManager = Mockery::mock(MessageManager::class);
        $messageManager->shouldReceive('storeUserMessage')->once();
        $messageManager->shouldReceive('storeAssistantMessage')
            ->once()
            ->with('conversation-1', 'نعم، اسمك حسن', []);

        $controller = new AiChatController(
            $this->mock(ConversationManager::class),
            $messageManager,
            $this->mock(ChatPipeline::class),
            $this->mock(StreamManager::class),
            app(FinalResponseFormatter::class),
        );

        $payload = new ChatPayload('فاكر اسمى');
        $payload->response = app(FinalResponseFormatter::class)->format(
            'tags, and the text must be exactly what the user should see. <final>نعم، اسمك حسن</final>',
            $payload->message,
        );
        $payload->setMetadata('session_id', 'session-12345');

        $method = new ReflectionMethod($controller, 'persistStreamResponse');
        $method->setAccessible(true);
        $method->invoke($controller, $payload, 'conversation-1');
    }

    private function controller(): AiChatController
    {
        return new AiChatController(
            $this->mock(ConversationManager::class),
            $this->mock(MessageManager::class),
            $this->mock(ChatPipeline::class),
            $this->mock(StreamManager::class),
            app(FinalResponseFormatter::class),
        );
    }
}
