<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Pipeline\ChatPayload;
use AiChat\Storage\AnonymousConversationStore;
use AiChat\Support\TokenCounter;
use Closure;

class RetrieveMemory
{
    public function __construct(
        protected AnonymousConversationStore $conversationStore,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if (! config('ai-chat.memory.enabled', false)) {
            return $next($payload);
        }

        if ($payload->executionPlan && ! $payload->executionPlan->useMemory) {
            return $next($payload);
        }

        $sessionId = $payload->metadata['session_id'] ?? null;

        if (! $sessionId) {
            return $next($payload);
        }

        try {
            $limit = (int) ($payload->executionPlan?->memoryLimit ?? config('ai-chat.context.memory_limit', 3));
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
            return $next($payload);
        }

        return $next($payload);
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
