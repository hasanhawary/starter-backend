<?php

namespace AiChat\Chat;

use AiChat\Support\TokenCounter;

class HistoryManager
{
    public function __construct(
        protected MessageManager $messageManager,
    ) {}

    public function getForAI(string $conversationId, int $limit = 20): array
    {
        $messages = $this->messageManager->getHistory($conversationId, $limit);

        return $messages->map(fn ($message) => [
            'role' => $message->role,
            'content' => $message->content,
        ])->values()->all();
    }

    public function buildMessages(string $conversationId, string $systemPrompt, int $limit = 20): array
    {
        $messages = [];

        if ($systemPrompt !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        $history = $this->getForAI($conversationId, $limit);

        return array_merge($messages, $history);
    }

    public function truncateIfNeeded(array $messages, int $maxTokens = 4000): array
    {
        $totalTokens = TokenCounter::estimateForMessages($messages);

        if (TokenCounter::withinBudget($totalTokens, $maxTokens)) {
            return $messages;
        }

        $systemMessages = array_filter($messages, fn (array $msg) => ($msg['role'] ?? '') === 'system');
        $nonSystemMessages = array_filter($messages, fn (array $msg) => ($msg['role'] ?? '') !== 'system');

        $systemTokens = TokenCounter::estimateForMessages(array_values($systemMessages));
        $remainingBudget = $maxTokens - $systemTokens;

        if ($remainingBudget <= 0) {
            return array_values($systemMessages);
        }

        $kept = [];
        $used = 0;

        foreach (array_reverse(array_values($nonSystemMessages)) as $message) {
            $tokens = TokenCounter::estimate((string) ($message['content'] ?? ''));

            if ($used + $tokens > $remainingBudget) {
                break;
            }

            $kept[] = $message;
            $used += $tokens;
        }

        return array_merge(array_values($systemMessages), array_reverse($kept));
    }
}
