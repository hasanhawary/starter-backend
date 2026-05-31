<?php

namespace AiChat\Pipeline;

class ExecutionPlan
{
    public string $intent = 'direct';

    public ?string $capability = null;

    public array $tools = [];

    public bool $useRag = false;

    public ?string $ragQuery = null;

    public int $ragLimit = 3;

    public bool $useMemory = false;

    public ?string $memoryQuery = null;

    public int $memoryLimit = 3;

    public int $historyLimit = 6;

    public string $historyMode = 'recent';

    public bool $needsClarification = false;

    public ?string $clarificationQuestion = null;

    public array $metadata = [];

    public string $planner = 'heuristic';

    public static function fromArray(array $data): self
    {
        $plan = new self;
        $plan->intent = $data['intent'] ?? 'direct';
        $plan->capability = $data['capability'] ?? null;
        $plan->tools = $data['tools'] ?? [];
        $plan->useRag = $data['use_rag'] ?? $data['useRag'] ?? false;
        $plan->ragQuery = $data['rag_query'] ?? $data['ragQuery'] ?? null;
        $plan->ragLimit = $data['rag_limit'] ?? $data['ragLimit'] ?? 3;
        $plan->useMemory = $data['use_memory'] ?? $data['useMemory'] ?? false;
        $plan->memoryQuery = $data['memory_query'] ?? $data['memoryQuery'] ?? null;
        $plan->memoryLimit = $data['memory_limit'] ?? $data['memoryLimit'] ?? 3;
        $plan->historyLimit = $data['history_limit'] ?? $data['historyLimit'] ?? 6;
        $plan->historyMode = $data['history_mode'] ?? $data['historyMode'] ?? 'recent';
        $plan->needsClarification = $data['needs_clarification'] ?? $data['needsClarification'] ?? false;
        $plan->clarificationQuestion = $data['clarification_question'] ?? $data['clarificationQuestion'] ?? null;
        $plan->metadata = $data['metadata'] ?? [];
        $plan->planner = $data['planner'] ?? 'heuristic';

        return $plan;
    }

    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'capability' => $this->capability,
            'tools' => $this->tools,
            'use_rag' => $this->useRag,
            'rag_query' => $this->ragQuery,
            'rag_limit' => $this->ragLimit,
            'use_memory' => $this->useMemory,
            'memory_query' => $this->memoryQuery,
            'memory_limit' => $this->memoryLimit,
            'history_limit' => $this->historyLimit,
            'history_mode' => $this->historyMode,
            'needs_clarification' => $this->needsClarification,
            'clarification_question' => $this->clarificationQuestion,
            'metadata' => $this->metadata,
            'planner' => $this->planner,
        ];
    }

    public function isSimpleLiveData(): bool
    {
        return in_array($this->intent, ['live_data', 'direct']) && ! $this->useRag && ! $this->useMemory;
    }

    public function isKnowledgeRequest(): bool
    {
        return $this->useRag || in_array($this->intent, ['knowledge', 'project_structure']);
    }

    public function isMemoryRequest(): bool
    {
        return $this->useMemory || in_array($this->intent, ['memory']);
    }

    public function requiresTools(): bool
    {
        return ! empty($this->tools) || in_array($this->intent, ['live_data', 'mixed']);
    }

    public function needsLlmCall(): bool
    {
        return ! $this->needsClarification;
    }
}
