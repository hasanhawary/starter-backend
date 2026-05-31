<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Memory\MemoryRetriever;
use AiChat\Pipeline\ChatPayload;
use AiChat\Storage\AnonymousConversationStore;
use AiChat\Support\TokenCounter;
use Closure;
use Illuminate\Support\Facades\Log;

class RetrieveMemory
{
    public function __construct(
        protected AnonymousConversationStore $conversationStore,
        protected MemoryRetriever $memoryRetriever,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if (! config('ai-chat.memory.enabled', false)) {
            return $next($payload);
        }

        if ($payload->executionPlan && ! $payload->executionPlan->useMemory) {
            return $next($payload);
        }

        $plan = $payload->executionPlan;
        $memoryQuery = $plan?->memoryQuery ?? $payload->message;
        $limit = (int) ($plan?->memoryLimit ?? config('ai-chat.context.memory_limit', 3));

        if ($memoryQuery) {
            $this->retrieveVectorMemories($payload, $memoryQuery, $limit);
        }

        $sessionId = $payload->metadata['session_id'] ?? null;

        if ($sessionId) {
            $this->retrieveConversationHistory($payload, $sessionId, $limit);
        }

        return $next($payload);
    }

    protected function retrieveVectorMemories(ChatPayload $payload, string $query, int $limit): void
    {
        try {
            $scope = $this->buildMemoryScope($payload);

            $memories = $this->memoryRetriever->retrieve(
                $query,
                $scope,
                $limit,
            );

            if (! empty($memories)) {
                foreach ($memories as $memory) {
                    $payload->memory[] = $memory['content'];
                }

                Log::debug('Vector memories retrieved', [
                    'memory_query' => mb_substr($query, 0, 100),
                    'count' => count($memories),
                    'scope' => $scope,
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('Vector memory retrieval failed, falling back to conversation history', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function buildMemoryScope(ChatPayload $payload): array
    {
        $scope = [];

        $userId = $payload->user?->getAuthIdentifier();
        if ($userId !== null && $userId !== '') {
            $scope['user_id'] = is_string($userId) ? $userId : (string) $userId;
        }

        if (isset($payload->metadata['guest_id'])) {
            $scope['guest_id'] = (string) $payload->metadata['guest_id'];
        } elseif (isset($payload->metadata['session_id'])) {
            $scope['guest_id'] = (string) $payload->metadata['session_id'];
        }

        if (isset($payload->metadata['tenant_id'])) {
            $scope['tenant_id'] = (string) $payload->metadata['tenant_id'];
        }

        if ($payload->agent !== null) {
            $scope['agent_id'] = $payload->agent->name();
        }

        return $scope;
    }

    protected function retrieveConversationHistory(ChatPayload $payload, string $sessionId, int $limit): void
    {
        try {
            $historyLimit = (int) config('ai-chat.context.history_limit', 6);

            $conversations = $this->conversationStore->getRecentConversations($sessionId, $limit);

            $budget = (int) config('ai-chat.memory.token_budget', 1000);
            $used = 0;

            foreach ($conversations as $conversationId) {
                $messages = $this->conversationStore->getLatestConversationMessages(
                    $conversationId,
                    $historyLimit,
                );

                foreach ($messages as $message) {
                    $content = $this->extractContent($message);

                    if (empty($content)) {
                        continue;
                    }

                    $tokens = TokenCounter::estimate($content);

                    if ($used + $tokens > $budget) {
                        break 2;
                    }

                    $payload->memory[] = $content;
                    $used += $tokens;
                }
            }
        } catch (\Throwable) {
            return;
        }
    }

    protected function extractContent(mixed $message): string
    {
        if (is_string($message)) {
            return $message;
        }

        if (is_array($message)) {
            return $message['content'] ?? json_encode($message);
        }

        if (method_exists($message, 'content')) {
            return (string) $message->content();
        }

        if (property_exists($message, 'content')) {
            return (string) $message->content;
        }

        if (method_exists($message, 'getText')) {
            return (string) $message->getText();
        }

        return '';
    }
}
