<?php

namespace AiChat\Memory;

use AiChat\Models\AiMemory;
use AiChat\Support\TokenCounter;
use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MemoryExtractor
{
    protected float $minImportance = 0.6;

    protected array $nameDeclarationPatterns = [
        'اسمي', 'اسمى', 'my name is', "my name's", 'my names ',
    ];

    protected array $preferencePatterns = [
        'remember that', 'my favorite', 'i prefer', 'i like', 'note that',
        'my preferred', 'i always focus on', 'i focus on',
        'remember', 'افتكر', 'تذكر', 'افضل', 'المفضل', 'المفضلة',
        'preference', 'setting', 'config',
    ];

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

        $nameData = $this->detectNameDeclaration($messages);
        if ($nameData !== null) {
            return $this->buildMemoryData($conversationId, $nameData['content'], 0.95, [
                'type' => 'user_profile',
                'key' => 'name',
                'value' => $nameData['value'],
            ]);
        }

        $preferenceData = $this->detectPreference($messages);
        if ($preferenceData !== null) {
            return $this->buildMemoryData($conversationId, $preferenceData['content'], 0.90, [
                'type' => 'preference',
                'key' => $preferenceData['key'],
                'value' => $preferenceData['value'],
            ]);
        }

        $importance = $this->scoreImportance($content, $messages);

        if ($importance < $this->minImportance) {
            return null;
        }

        $summary = $this->summarizeFacts($content);

        if (empty(trim($summary)) || mb_strlen($summary) < 10) {
            return null;
        }

        return $this->buildMemoryData($conversationId, $summary, $importance, [
            'type' => 'conversation_summary',
        ]);
    }

    public function extractFromExchange(string $conversationId, string $userMessage, string $assistantResponse, array $context = []): ?array
    {
        if (! config('ai-chat.memory.enabled', false)) {
            return null;
        }

        $nameData = $this->detectNameFromMessage($userMessage);
        if ($nameData !== null) {
            return $this->buildMemoryData($conversationId, $nameData['content'], 0.95, [
                'type' => 'user_profile',
                'key' => 'name',
                'value' => $nameData['value'],
            ], $context);
        }

        $preferenceData = $this->detectPreferenceFromMessage($userMessage, $assistantResponse);
        if ($preferenceData !== null) {
            return $this->buildMemoryData($conversationId, $preferenceData['content'], 0.90, [
                'type' => $preferenceData['type'],
                'key' => $preferenceData['key'],
                'value' => $preferenceData['value'],
            ], $context);
        }

        $content = "[user] {$userMessage}\n[assistant] {$assistantResponse}";
        $importance = $this->scoreFastImportance($content, $userMessage, $assistantResponse);

        if ($importance < $this->minImportance) {
            return null;
        }

        $summary = $this->extractFastSummary($userMessage, $assistantResponse);

        if (empty(trim($summary)) || mb_strlen($summary) < 10) {
            return null;
        }

        return $this->buildMemoryData($conversationId, $summary, $importance, [
            'type' => 'exchange_summary',
        ], $context);
    }

    public function store(array $memoryData): ?AiMemory
    {
        try {
            $embedding = $this->embeddings->generate($memoryData['content']);

            $data = [
                'id' => $memoryData['id'] ?? (string) Str::uuid(),
                'conversation_id' => $memoryData['conversation_id'],
                'user_id' => $memoryData['user_id'] ?? null,
                'guest_id' => $memoryData['guest_id'] ?? null,
                'tenant_id' => $memoryData['tenant_id'] ?? null,
                'agent_id' => $memoryData['agent_id'] ?? null,
                'content' => $memoryData['content'],
                'embedding' => $embedding,
                'metadata' => $memoryData['metadata'] ?? [],
            ];

            return AiMemory::create($data);
        } catch (\Throwable $e) {
            Log::warning('Failed to store extracted memory', [
                'error' => $e->getMessage(),
                'conversation_id' => $memoryData['conversation_id'] ?? null,
            ]);

            return null;
        }
    }

    protected function buildMemoryData(string $conversationId, string $content, float $importance, array $metadata, array $scope = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversationId,
            'content' => $content,
            'importance' => $importance,
            'metadata' => array_merge($metadata, [
                'extracted_at' => now()->toIso8601String(),
            ]),
        ], $scope);
    }

    protected function detectNameDeclaration(array $messages): ?array
    {
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') !== 'user') {
                continue;
            }

            $content = $msg['content'] ?? '';

            return $this->detectNameFromMessage($content);
        }

        return null;
    }

    protected function detectNameFromMessage(string $message): ?array
    {
        $normalized = mb_strtolower(trim($message));

        foreach ($this->nameDeclarationPatterns as $pattern) {
            $normPattern = mb_strtolower($pattern);
            $pos = mb_strpos($normalized, $normPattern);

            if ($pos === false) {
                continue;
            }

            $afterName = trim(mb_substr($message, $pos + mb_strlen($normPattern)));

            if ($afterName === '' || $this->startsWithQuestionWord($afterName)) {
                continue;
            }

            $nameParts = preg_split('/[\s،,]+/u', $afterName, 2);
            $name = trim($nameParts[0]);

            if (mb_strlen($name) < 2) {
                continue;
            }

            return [
                'value' => $name,
                'content' => "User name: {$name}",
            ];
        }

        return null;
    }

    protected function detectPreference(array $messages): ?array
    {
        $userText = '';
        $assistantText = '';

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? '';
            $content = $msg['content'] ?? '';

            if ($role === 'user') {
                $userText .= ' '.$content;
            } elseif ($role === 'assistant') {
                $assistantText .= ' '.$content;
            }
        }

        return $this->detectPreferenceFromMessage(trim($userText), trim($assistantText));
    }

    protected function detectPreferenceFromMessage(string $userMessage, string $assistantResponse): ?array
    {
        $lowerUser = mb_strtolower($userMessage);

        foreach ($this->preferencePatterns as $pattern) {
            $normPattern = mb_strtolower($pattern);

            if (! str_contains($lowerUser, $normPattern)) {
                continue;
            }

            $key = $this->extractPreferenceKey($userMessage);
            $value = $this->extractPreferenceValue($userMessage);

            return [
                'type' => $key ? 'preference' : 'note',
                'key' => $key ?: 'general',
                'value' => $value ?: $userMessage,
                'content' => $key ? "User preference: {$key} = {$value}" : "Note: {$userMessage}",
            ];
        }

        return null;
    }

    protected function extractPreferenceKey(string $message): ?string
    {
        $patterns = [
            '/my favourite (\w+)/i',
            '/my favorite (\w+)/i',
            '/my preferred (\w+)/i',
            '/focus on ([^?!.]+)/i',
            '/(\w+) preference/i',
            '/افضل (\w+)/u',
            '/المفضل (\w+)/u',
            '/المفضلة (\w+)/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return mb_strtolower($matches[1]);
            }
        }

        return null;
    }

    protected function extractPreferenceValue(string $message): ?string
    {
        $patterns = [
            '/is (\w+(?:\s+\w+){0,3})/i',
            '/focus on ([^?!.]+)/i',
            '/(\w+(?:\s+\w+){0,2}) colour/i',
            '/(\w+(?:\s+\w+){0,2}) color/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    protected function scoreFastImportance(string $fullContent, string $userMessage, string $assistantResponse): float
    {
        $score = 0.5;

        $indicators = [
            'remember' => 0.2,
            'important' => 0.15,
            'decision' => 0.15,
            'chose' => 0.1,
            'agreed' => 0.1,
            'report' => 0.12,
            'analysis' => 0.1,
            'conclusion' => 0.12,
            'finding' => 0.1,
            'note' => 0.08,
            'action item' => 0.15,
            'follow up' => 0.12,
            'setting' => 0.1,
            'config' => 0.1,
        ];

        $lowerContent = mb_strtolower($fullContent);

        foreach ($indicators as $indicator => $weight) {
            if (str_contains($lowerContent, $indicator)) {
                $score = min(1.0, $score + $weight);
            }
        }

        $tokenCount = TokenCounter::estimate($fullContent);

        if ($tokenCount < 10) {
            $score *= 0.6;
        } elseif ($tokenCount > 100) {
            $score = min(1.0, $score + 0.05);
        }

        return round(min(1.0, max(0.0, $score)), 2);
    }

    protected function extractFastSummary(string $userMessage, string $assistantResponse): string
    {
        $skipPatterns = [
            '/^hi$/i', '/^hello$/i', '/^thanks/i', '/^thank you/i',
            '/^ok$/i', '/^okay$/i', '/^sure$/i', '/^great$/i',
            '/^perfect$/i', '/^yes$/i', '/^no$/i', '/^cool$/i', '/^nice$/i',
        ];

        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, trim($userMessage))) {
                return '';
            }
        }

        if (mb_strlen($userMessage) > mb_strlen($assistantResponse)) {
            $summary = $userMessage;
        } else {
            $summary = "{$userMessage} - {$assistantResponse}";
        }

        return mb_substr($summary, 0, 300);
    }

    protected function startsWithQuestionWord(string $text): bool
    {
        $questionWords = [
            'ايه', 'اي', 'مين', 'اين', 'فين', 'امتى', 'ليه',
            'ازاي', 'ايش', 'هل', 'كيف', 'ما', 'ماذا', 'من',
            'what', 'who', 'where', 'when', 'why', 'how', 'is',
            'are', 'do', 'does',
        ];

        foreach ($questionWords as $qw) {
            if (str_starts_with($text, $qw) || str_starts_with($text, mb_strtolower($qw))) {
                return true;
            }
        }

        return false;
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
                '/^hi$/i', '/^hello$/i', '/^thanks/i', '/^thank you/i',
                '/^ok$/i', '/^okay$/i', '/^sure$/i', '/^great$/i',
                '/^perfect$/i', '/^yes$/i', '/^no$/i', '/^cool$/i', '/^nice$/i',
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
