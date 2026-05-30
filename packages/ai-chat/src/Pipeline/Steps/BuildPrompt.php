<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Chat\HistoryManager;
use AiChat\Pipeline\ChatPayload;
use AiChat\Support\TokenBudgetManager;
use Closure;

class BuildPrompt
{
    public function __construct(
        protected HistoryManager $historyManager,
        protected TokenBudgetManager $budgetManager,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $systemPrompt = $this->buildSystemPrompt($payload);

        $messages = [];

        if ($systemPrompt !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        $conversationId = $payload->conversationId();

        if ($conversationId) {
            $historyLimit = $this->resolveHistoryLimit($payload);
            $history = $this->historyManager->getForAI($conversationId, $historyLimit);
            $messages = array_merge($messages, $history);
        }

        $messages[] = [
            'role' => 'user',
            'content' => $payload->message,
        ];

        $maxTokens = $this->budgetManager->getMaxContextTokens();
        $messages = $this->historyManager->truncateIfNeeded($messages, $maxTokens);

        $payload->messages = $messages;

        return $next($payload);
    }

    protected function buildSystemPrompt(ChatPayload $payload): string
    {
        $parts = [];
        $plan = $payload->executionPlan;

        $agentPrompt = $payload->agent?->systemPrompt() ?? config('ai-chat.conversations.default_system_prompt', '');

        if ($agentPrompt !== '') {
            $parts[] = $agentPrompt;
        }

        if ($plan) {
            if ($plan->isSimpleLiveData()) {
                $parts[] = "\n\nUse the available tools to answer this live-data question. Do not invent values.";
            } elseif ($plan->isKnowledgeRequest()) {
                $parts[] = "\n\nAnswer only from retrieved project knowledge. If missing, say you do not have enough information.";
            } elseif ($plan->isMemoryRequest()) {
                $parts[] = "\n\nUse the conversation memory context to provide a relevant response.";
            }
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

    protected function resolveHistoryLimit(ChatPayload $payload): int
    {
        if ($payload->executionPlan && $payload->executionPlan->historyLimit > 0) {
            return $this->budgetManager->getHistoryLimit($payload->executionPlan->historyLimit);
        }

        return (int) config('ai-chat.conversations.history_limit', 6);
    }
}
