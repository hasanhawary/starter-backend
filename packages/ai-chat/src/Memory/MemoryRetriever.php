<?php

namespace AiChat\Memory;

use AiChat\Models\AiMemory;
use AiChat\Vector\EmbeddingGenerator;

class MemoryRetriever
{
    protected float $threshold = 0.6;

    protected int $prefetchLimit = 50;

    public function __construct(
        protected EmbeddingGenerator $embeddings,
    ) {}

    public function retrieve(string $query, array|string $scope = [], int $limit = 5): array
    {
        if (is_string($scope) && $scope !== '') {
            $scope = ['conversation_id' => $scope];
        }

        $embedding = $this->embeddings->generate($query);

        if (! empty(array_filter($embedding))) {
            $vectorResults = $this->vectorSearch($embedding, $scope, $limit);

            if (! empty($vectorResults)) {
                return $vectorResults;
            }
        }

        return $this->textSearch($query, $scope, $limit);
    }

    public function retrieveByConversation(string $conversationId, string $query, int $limit = 5, array $scope = []): array
    {
        $scope['conversation_id'] = $conversationId;

        return $this->retrieve($query, $scope, $limit);
    }

    protected function vectorSearch(array $embedding, array $scope, int $limit): array
    {
        return $this->searchAiMemories($embedding, $scope, $limit);
    }

    protected function searchAiMemories(array $embedding, array $scope, int $limit): array
    {
        $query = AiMemory::whereNotNull('embedding');

        $this->applyScope($query, $scope);

        $memories = $query->select(['id', 'content', 'embedding', 'metadata'])
            ->limit($this->prefetchLimit)
            ->get();

        $scored = [];

        foreach ($memories as $memory) {
            $memoryEmbedding = is_array($memory->embedding)
                ? $memory->embedding
                : json_decode($memory->embedding, true);

            if (! is_array($memoryEmbedding)) {
                continue;
            }

            $scored[] = [
                'id' => $memory->id,
                'content' => $memory->content,
                'score' => $this->cosineSimilarity($embedding, $memoryEmbedding),
                'metadata' => $memory->metadata ?? [],
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(
            array_values(array_filter($scored, fn ($r) => $r['score'] >= $this->threshold)),
            0,
            $limit,
        );
    }

    protected function textSearch(string $query, array $scope, int $limit): array
    {
        $keywords = $this->extractKeywords($query);

        if (empty($keywords)) {
            return [];
        }

        $dbQuery = AiMemory::query();
        $this->applyScope($dbQuery, $scope);

        $dbQuery->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('content', 'LIKE', "%{$keyword}%");
                $q->orWhere('metadata', 'LIKE', "%{$keyword}%");
            }
        });

        $memories = $dbQuery->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $memories->map(function (AiMemory $memory) use ($query) {
            return [
                'content' => $memory->content,
                'score' => $this->calculateScore($memory->content, $query),
                'metadata' => $memory->metadata ?? [],
            ];
        })->sortByDesc('score')->values()->toArray();
    }

    protected function applyScope($query, array $scope): void
    {
        foreach (['conversation_id', 'user_id', 'guest_id', 'tenant_id', 'agent_id'] as $key) {
            if (array_key_exists($key, $scope) && $scope[$key] !== null && $scope[$key] !== '') {
                $query->where($key, $scope[$key]);
            }
        }
    }

    protected function extractKeywords(string $query): array
    {
        $cleaned = preg_replace('/[؟?،,!.:\-]+/u', ' ', $query);
        $words = preg_split('/\s+/u', trim((string) $cleaned));

        $stopWords = [
            'فاكر', 'تفتكر', 'تذكر', 'هل', 'ما', 'ماذا', 'من', 'اين', 'كيف', 'لماذا',
            'do', 'you', 'your', 'what', 'is', 'are', 'the', 'my', 'me', 'can',
            'اسم', 'say', 'remember', 'tell', 'about', 'the', 'a', 'an',
        ];

        return array_values(array_filter(array_map(function (string $word) use ($stopWords) {
            $word = trim($word);
            $word = mb_strtolower($word);

            if ($word === '' || mb_strlen($word) < 2 || in_array($word, $stopWords, true)) {
                return null;
            }

            return $word;
        }, $words)));
    }

    protected function calculateScore(string $content, string $query): float
    {
        $contentLower = mb_strtolower($content);
        $queryLower = mb_strtolower($query);
        $words = array_filter(explode(' ', $queryLower));
        $matches = 0;

        foreach ($words as $word) {
            if (str_contains($contentLower, $word)) {
                $matches++;
            }
        }

        return count($words) > 0 ? $matches / count($words) : 0;
    }

    protected function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;
        $count = min(count($a), count($b));

        if ($count === 0) {
            return 0.0;
        }

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $magnitudeA += $a[$i] * $a[$i];
            $magnitudeB += $b[$i] * $b[$i];
        }

        $denominator = sqrt($magnitudeA) * sqrt($magnitudeB);

        return $denominator > 0 ? $dotProduct / $denominator : 0.0;
    }
}
