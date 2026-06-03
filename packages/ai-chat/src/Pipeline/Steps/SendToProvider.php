<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Agents\ChatAgent;
use AiChat\Chat\ConversationManager;
use AiChat\Chat\HistorySelector;
use AiChat\Pipeline\ChatPayload;
use AiChat\Prompt\SystemPromptBuilder;
use AiChat\Response\FinalResponseFormatter;
use Closure;

class SendToProvider
{
    public function __construct(
        protected FinalResponseFormatter $formatter,
        protected SystemPromptBuilder $promptBuilder,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if (! $payload->agent) {
            $payload->addError('AI provider is not configured. Please set up an AI provider first.', 503);

            return $payload;
        }

        $chatAgent = $this->buildChatAgent($payload);

        if ($payload->streaming) {
            return $this->handleStreaming($payload, $chatAgent, $next);
        }

        return $this->handleSync($payload, $chatAgent, $next);
    }

    protected function buildChatAgent(ChatPayload $payload): ChatAgent
    {
        $this->ensureConversation($payload);

        $systemPrompt = $this->promptBuilder->build($payload);

        $agent = new ChatAgent($systemPrompt);

        $sessionId = $payload->metadata['session_id'] ?? null;
        $conversationId = $payload->conversationId();

        if ($conversationId && $sessionId) {
            $agent->continue($conversationId, $sessionId);
        } elseif ($sessionId) {
            $agent->forSession($sessionId);
        }

        if (! empty($payload->tools)) {
            $agent->withTools(array_keys($payload->tools));
        }

        $agent->withToolContextPayload([
            'conversation_id' => $conversationId,
            'session_id' => $sessionId,
            'message' => $payload->message,
        ]);

        if ($payload->executionPlan) {
            $agent->withExecutionPlan($payload->executionPlan);
            $agent->withCurrentMessage($payload->message);

            $selector = app(HistorySelector::class);
            $policy = $selector->select($payload->executionPlan, $payload->message);
            $agent->withHistoryPolicy($policy);
        }

        return $agent;
    }

    protected function ensureConversation(ChatPayload $payload): void
    {
        if ($payload->conversationId()) {
            return;
        }

        $sessionId = $payload->metadata['session_id'] ?? null;

        if (! is_string($sessionId) || $sessionId === '') {
            return;
        }

        $payload->conversation = app(ConversationManager::class)->create(
            $sessionId,
            'New Chat',
        );
    }

    protected function handleSync(ChatPayload $payload, ChatAgent $chatAgent, Closure $next): ChatPayload
    {
        try {
            $response = $chatAgent->prompt($payload->message);
        } catch (\Throwable $e) {
            $payload->addError('AI provider error: '.$e->getMessage(), 503);

            return $payload;
        }

        $payload->response = $this->formatter->format(
            $response->text ?? (string) $response,
            $payload->message,
            $payload->executionPlan,
        );

        if (isset($response->usage)) {
            $payload->setMetadata('usage', (array) $response->usage);
        }

        $toolCalls = collect($response->toolCalls ?? []);

        if ($toolCalls->isNotEmpty()) {
            $payload->setMetadata('tool_calls', $toolCalls
                ->map(fn ($toolCall) => $this->normalizeToolCall($toolCall))
                ->values()
                ->toArray());

            $payload->setMetadata('tool_calls_used', true);
        }

        return $next($payload);
    }

    protected function handleStreaming(ChatPayload $payload, ChatAgent $chatAgent, Closure $next): ChatPayload
    {
        try {
            $stream = $chatAgent->stream($payload->message);
            $fullContent = '';

            foreach ($stream as $event) {
                $content = (string) ($event->text ?? '');
                $fullContent .= $content;
            }

            $payload->response = $this->formatter->format(
                $fullContent,
                $payload->message,
                $payload->executionPlan,
            );
            $payload->setMetadata('streamed', true);

            return $next($payload);
        } catch (\Throwable $e) {
            $payload->addError('AI provider error: '.$e->getMessage(), 503);

            return $payload;
        }
    }

    protected function normalizeToolCall(mixed $toolCall): array
    {
        $arguments = data_get($toolCall, 'arguments', []);

        if (is_string($arguments)) {
            $decoded = json_decode($arguments, true);
            $arguments = json_last_error() === JSON_ERROR_NONE ? $decoded : $arguments;
        }

        return [
            'id' => data_get($toolCall, 'id'),
            'name' => data_get($toolCall, 'name')
                ?? data_get($toolCall, 'function.name')
                ?? data_get($toolCall, 'toolName'),
            'arguments' => $arguments,
        ];
    }
}
