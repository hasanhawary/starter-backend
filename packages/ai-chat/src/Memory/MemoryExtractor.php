<?php

namespace AiChat\Memory;

use AiChat\Models\AiMemory;
use AiChat\Support\TokenCounter;
use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Support\Facades\Log;

class MemoryExtractor
{
    protected float $minImportance = 0.6;

    public function __construct(
        protected EmbeddingGenerator $embeddings,
    ) {}

    public function shouldExtract(string $conversationId, int $messageCount): bool
    {
        if (! config('ai-chat.memory.enabled', false)) {
            return false;
        }

        $extractAfter = (int) config('ai-chat.memory.extract_after_messages', 6);

        if ($extractAfter <= 0) {
            return config('ai-chat.memory.store_every_message', false);
        }

        return $messageCount % $extractAfter === 0 && $messageCount > 0;
    }

    public function extract(string $conversationId, array $messages): ?array
    {
        $content = $this->buildExtractionContent($messages);

        if (empty(trim($content))) {
            return null;
        }

        $importance = $this->scoreImportance($content, $messages);

        if ($importance < $this->minImportance) {
            return null;
        }

        $summary = $this->summarizeFacts($content);

        if (empty(trim($summary)) || mb_strlen($summary) < 10) {
            return null;
        }

        return [
            'conversation_id' => $conversationId,
            'content' => $summary,
            'importance' => $importance,
            'metadata' => [
                'message_count' => count($messages),
                'extracted_at' => now()->toIso8601String(),
                'type' => 'conversation_summary',
            ],
        ];
    }

    public function store(array $memoryData): ?AiMemory
    {
        try {
            $embedding = $this->embeddings->generate($memoryData['content']);

            return AiMemory::create([
                'conversation_id' => $memoryData['conversation_id'],
                'content' => $memoryData['content'],
                'embedding' => $embedding,
                'metadata' => $memoryData['metadata'] ?? [],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to store extracted memory', [
                'error' => $e->getMessage(),
                'conversation_id' => $memoryData['conversation_id'] ?? null,
            ]);

            return null;
        }
    }

    protected function buildExtractionContent(array $messages): string
    {
        $parts = [];

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'unknown';
            $content = $msg['content'] ?? '';

            if ($role === 'tool') {
                continue;
            }

            if (empty(trim($content))) {
                continue;
            }

            $parts[] = "[{$role}] {$content}";
        }

        return implode("\n", $parts);
    }

    protected function scoreImportance(string $content, array $messages): float
    {
        $score = 0.5;

        $indicators = [
            'preference' => 0.15,
            'config' => 0.1,
            'setting' => 0.1,
            'decision' => 0.15,
            'chose' => 0.1,
            'agreed' => 0.1,
            'report' => 0.12,
            'analysis' => 0.1,
            'conclusion' => 0.12,
            'finding' => 0.1,
            'remember' => 0.2,
            'important' => 0.15,
            'note' => 0.08,
            'action item' => 0.15,
            'follow up' => 0.12,
        ];

        $lowerContent = mb_strtolower($content);

        foreach ($indicators as $indicator => $weight) {
            if (str_contains($lowerContent, $indicator)) {
                $score = min(1.0, $score + $weight);
            }
        }

        $userMessages = array_filter($messages, fn ($m) => ($m['role'] ?? '') === 'user');

        if (count($userMessages) >= 3) {
            $score = min(1.0, $score + 0.05);
        }

        $hasToolResults = array_filter($messages, fn ($m) => isset($m['tool_results']) || str_contains(($m['content'] ?? ''), '"tool'));

        if (! empty($hasToolResults)) {
            $score = min(1.0, $score + 0.08);
        }

        $tokenCount = TokenCounter::estimate($content);

        if ($tokenCount < 20) {
            $score *= 0.7;
        } elseif ($tokenCount > 500) {
            $score = min(1.0, $score + 0.05);
        }

        return round(min(1.0, max(0.0, $score)), 2);
    }

    protected function summarizeFacts(string $content): string
    {
        $lines = explode("\n", trim($content));
        $facts = [];
        $currentFact = '';

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (preg_match('/^\[(user|assistant)\]/i', $line)) {
                $line = preg_replace('/^\[(user|assistant)\]\s*/i', '', $line);
            }

            $skipPatterns = [
                '/^hi$/i',
                '/^hello$/i',
                '/^thanks/i',
                '/^thank you/i',
                '/^ok$/i',
                '/^okay$/i',
                '/^sure$/i',
                '/^great$/i',
                '/^perfect$/i',
                '/^yes$/i',
                '/^no$/i',
                '/^cool$/i',
                '/^nice$/i',
            ];

            $shouldSkip = false;

            foreach ($skipPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $shouldSkip = true;
                    break;
                }
            }

            if ($shouldSkip) {
                continue;
            }

            if (mb_strlen($currentFact."\n".$line) > 300) {
                if (! empty(trim($currentFact))) {
                    $facts[] = trim($currentFact);
                }

                $currentFact = $line;
            } else {
                $currentFact = empty($currentFact) ? $line : $currentFact.' '.$line;
            }
        }

        if (! empty(trim($currentFact))) {
            $facts[] = trim($currentFact);
        }

        $maxFacts = 5;
        $facts = array_slice($facts, 0, $maxFacts);

        return implode('. ', $facts);
    }
}
