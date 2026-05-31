<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Chat\ConversationManager;
use AiChat\Chat\MessageManager;
use AiChat\Memory\MemoryExtractor;
use AiChat\Models\AiUsageLog;
use AiChat\Pipeline\ChatPayload;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PersistResponse
{
    public function __construct(
        protected MessageManager $messageManager,
        protected ConversationManager $conversationManager,
        protected MemoryExtractor $memoryExtractor,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if ($payload->response === null && empty($payload->toolResults)) {
            return $next($payload);
        }

        $conversationId = $payload->conversationId();

        if (! $conversationId) {
            $sessionId = $payload->metadata['session_id'] ?? null;
            $conversation = $this->conversationManager->create(
                is_string($sessionId) ? $sessionId : null,
                'New Chat',
            );
            $payload->conversation = $conversation;
            $conversationId = $conversation->id;
        }

        $this->persistUserMessage($payload, $conversationId);

        $this->persistAssistantResponse($payload, $conversationId);

        $this->persistUsage($payload, $conversationId);

        if ($payload->response !== null) {
            $this->extractMemory($payload, $conversationId);
        }

        return $next($payload);
    }

    protected function persistUserMessage(ChatPayload $payload, string $conversationId): void
    {
        $userId = $payload->user?->getAuthIdentifier();
        $sessionId = $payload->metadata['session_id'] ?? null;

        $this->messageManager->storeUserMessage(
            $conversationId,
            $payload->message,
            is_string($sessionId) ? $sessionId : (is_string($userId) ? $userId : (string) $userId),
            $payload->metadata,
        );
    }

    protected function persistAssistantResponse(ChatPayload $payload, string $conversationId): void
    {
        if (! empty($payload->toolResults)) {
            $toolCalls = $payload->rawResponse['tool_calls'] ?? [];
            $toolResults = array_map(fn ($result) => $result->toArray(), $payload->toolResults);

            $this->messageManager->storeToolCallMessage($conversationId, $toolCalls, $toolResults);

            return;
        }

        if ($payload->response !== null) {
            $metadata = [];

            if (isset($payload->metadata['usage'])) {
                $metadata['usage'] = $payload->metadata['usage'];
            }

            if (isset($payload->metadata['tool_calls'])) {
                $metadata['tool_calls'] = $payload->metadata['tool_calls'];
            }

            if (isset($payload->metadata['tool_results'])) {
                $metadata['tool_results'] = $payload->metadata['tool_results'];
            }

            $this->messageManager->storeAssistantMessage(
                $conversationId,
                $payload->response,
                $metadata,
            );
        }
    }

    protected function persistUsage(ChatPayload $payload, string $conversationId): void
    {
        $usage = $payload->metadata['usage'] ?? null;

        if (! is_array($usage)) {
            return;
        }

        try {
            $inputTokens = (int) ($usage['promptTokens'] ?? $usage['prompt_tokens'] ?? 0);
            $outputTokens = (int) ($usage['completionTokens'] ?? $usage['completion_tokens'] ?? 0);

            AiUsageLog::create([
                'id' => (string) Str::uuid7(),
                'user_id' => $payload->user?->getAuthIdentifier(),
                'conversation_id' => $conversationId,
                'provider' => (string) config('ai-chat.provider', 'openai'),
                'model' => (string) config('ai-chat.model', 'unknown'),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $inputTokens + $outputTokens,
                'cost' => 0,
                'latency_ms' => null,
                'status' => $payload->hasErrors() ? 'failed' : 'success',
                'error' => $payload->firstError(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist AI usage log', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function extractMemory(ChatPayload $payload, string $conversationId): void
    {
        if (! config('ai-chat.memory.enabled', false)) {
            return;
        }

        $userMessage = $payload->message;
        $assistantResponse = $payload->response;

        if (empty(trim($userMessage)) || empty(trim($assistantResponse))) {
            return;
        }

        try {
            $context = [];

            $userId = $payload->user?->getAuthIdentifier();
            if ($userId) {
                $context['user_id'] = is_string($userId) ? $userId : (string) $userId;
            }

            if (isset($payload->metadata['session_id'])) {
                $context['guest_id'] = (string) $payload->metadata['session_id'];
            } elseif (isset($payload->metadata['guest_id'])) {
                $context['guest_id'] = (string) $payload->metadata['guest_id'];
            }

            if (isset($payload->metadata['tenant_id'])) {
                $context['tenant_id'] = $payload->metadata['tenant_id'];
            }

            if ($payload->agent !== null) {
                $context['agent_id'] = $payload->agent->name();
            }

            $memoryData = $this->memoryExtractor->extractFromExchange(
                $conversationId,
                $userMessage,
                $assistantResponse,
                $context,
            );

            if ($memoryData === null) {
                return;
            }

            $this->memoryExtractor->store($memoryData);

            Log::debug('Memory extracted and stored', [
                'conversation_id' => $conversationId,
                'content' => mb_substr($memoryData['content'], 0, 100),
                'type' => $memoryData['metadata']['type'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to extract memory from exchange', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
            ]);
        }
    }
}
