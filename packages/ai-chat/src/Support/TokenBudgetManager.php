<?php

namespace AiChat\Support;

class TokenBudgetManager
{
    protected int $maxContextTokens;

    protected int $systemPromptBudget;

    protected int $historyLimit;

    protected int $ragLimit;

    protected int $memoryLimit;

    protected int $maxTools;

    protected int $maxToolResultItems;

    public function __construct()
    {
        $this->maxContextTokens = (int) config('ai-chat.context.max_context_tokens', 8000);
        $this->systemPromptBudget = (int) config('ai-chat.context.system_prompt_budget', 1500);
        $this->historyLimit = (int) config('ai-chat.context.history_limit', 6);
        $this->ragLimit = (int) config('ai-chat.context.rag_limit', 3);
        $this->memoryLimit = (int) config('ai-chat.context.memory_limit', 3);
        $this->maxTools = (int) config('ai-chat.context.max_tools', 5);
        $this->maxToolResultItems = (int) config('ai-chat.context.max_tool_result_items', 10);
    }

    public function getMaxContextTokens(): int
    {
        return $this->maxContextTokens;
    }

    public function getSystemPromptBudget(): int
    {
        return $this->systemPromptBudget;
    }

    public function getHistoryLimit(?int $planHistoryLimit = null): int
    {
        if ($planHistoryLimit !== null && $planHistoryLimit > 0) {
            return min($planHistoryLimit, $this->historyLimit);
        }

        return $this->historyLimit;
    }

    public function getRagLimit(?int $planRagLimit = null): int
    {
        if ($planRagLimit !== null && $planRagLimit > 0) {
            return min($planRagLimit, $this->ragLimit);
        }

        return $this->ragLimit;
    }

    public function getMemoryLimit(?int $planMemoryLimit = null): int
    {
        if ($planMemoryLimit !== null && $planMemoryLimit > 0) {
            return min($planMemoryLimit, $this->memoryLimit);
        }

        return $this->memoryLimit;
    }

    public function getMaxTools(): int
    {
        return $this->maxTools;
    }

    public function getMaxToolResultItems(): int
    {
        return $this->maxToolResultItems;
    }

    public function calculateAvailableForContent(int $systemTokens, int $historyTokens = 0): int
    {
        return max(0, $this->maxContextTokens - $systemTokens - $historyTokens - 500);
    }

    public function needsTrimming(array $messages): bool
    {
        return TokenCounter::estimateForMessages($messages) > $this->maxContextTokens;
    }

    public function trimToBudget(array $items, int $budget, string $type = 'general'): array
    {
        $used = 0;
        $kept = [];

        foreach ($items as $item) {
            $content = is_array($item) ? json_encode($item) : (string) $item;
            $tokens = TokenCounter::estimate($content);

            if ($used + $tokens > $budget) {
                break;
            }

            $kept[] = $item;
            $used += $tokens;
        }

        return $kept;
    }

    public function getPriorityOrder(): array
    {
        return ['system', 'user_message', 'tools', 'rag', 'memory', 'history'];
    }

    public function reduceByPriority(array &$components): void
    {
        $order = $this->getPriorityOrder();

        while (TokenCounter::estimateForMessages($this->flattenComponents($components)) > $this->maxContextTokens && ! empty($order)) {
            $target = array_shift($order);

            if ($target === 'system') {
                continue;
            }

            match ($target) {
                'history' => $this->reduceComponent($components, 'history', 0.3),
                'memory' => $this->reduceComponent($components, 'memory', 0),
                'rag' => $this->reduceComponent($components, 'rag', 0),
                'tools' => $this->reduceComponent($components, 'tools', 1),
                default => null,
            };
        }
    }

    protected function reduceComponent(array &$components, string $key, float $keepRatio): void
    {
        if (! isset($components[$key]) || empty($components[$key])) {
            return;
        }

        if ($keepRatio <= 0) {
            $components[$key] = [];

            return;
        }

        $count = count($components[$key]);
        $keepCount = max(1, (int) ceil($count * $keepRatio));
        $components[$key] = array_slice($components[$key], 0, $keepCount);
    }

    protected function flattenComponents(array $components): array
    {
        $flat = [];

        foreach ($components as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $flat[] = is_array($item) ? $item : ['content' => (string) $item];
                }
            } else {
                $flat[] = ['content' => (string) $value];
            }
        }

        return $flat;
    }
}
