<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Chat\HistoryManager;
use AiChat\Pipeline\ChatPayload;
use Closure;

class BuildPrompt
{
    public function __construct(
        protected HistoryManager $historyManager,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $systemPrompt = $this->buildSystemPrompt($payload);

        $conversationId = $payload->conversationId();

        $messages = [];

        if ($systemPrompt !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        if ($conversationId) {
            $historyLimit = (int) config('ai-chat.conversations.history_limit', 20);
            $history = $this->historyManager->getForAI($conversationId, $historyLimit);
            $messages = array_merge($messages, $history);
        }

        $messages[] = [
            'role' => 'user',
            'content' => $payload->message,
        ];

        $maxTokens = (int) config('ai-chat.conversations.max_prompt_tokens', 4000);
        $messages = $this->historyManager->truncateIfNeeded($messages, $maxTokens);

        $payload->messages = $messages;

        return $next($payload);
    }

    protected function buildSystemPrompt(ChatPayload $payload): string
    {
        $parts = [];

        $agentPrompt = $payload->agent?->systemPrompt() ?? config('ai-chat.conversations.default_system_prompt', '');

        if ($agentPrompt !== '') {
            $parts[] = $agentPrompt;
        }

        if (! empty($payload->context)) {
            $parts[] = "\n\n## Context\n".json_encode($payload->context, JSON_PRETTY_PRINT);
        }

        if (! empty($payload->knowledge)) {
            $parts[] = "\n\n## Knowledge Base\n".collect($payload->knowledge)
                ->map(fn ($k, $i) => '['.($i + 1).'] '.(is_array($k) ? json_encode($k) : (string) $k))
                ->implode("\n");
        }

        if (! empty($payload->memory)) {
            $parts[] = "\n\n## Conversation Memory\n".collect($payload->memory)
                ->map(fn ($m) => is_array($m) ? json_encode($m) : (string) $m)
                ->implode("\n");
        }

        return implode('', $parts);
    }
}
