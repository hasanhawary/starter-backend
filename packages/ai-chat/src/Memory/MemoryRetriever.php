<?php

namespace AiChat\Memory;

use AiChat\Models\AiMemory;
use AiChat\Vector\EmbeddingGenerator;
use AiChat\Vector\VectorManager;

class MemoryRetriever
{
    public function __construct(
        protected EmbeddingGenerator $embeddings,
        protected VectorManager $vectorManager,
    ) {}

    public function retrieve(string $query, ?string $conversationId = null, int $limit = 5): array
    {
        $embedding = $this->embeddings->generate($query);

        if (! empty(array_filter($embedding))) {
            $vectorResults = $this->vectorSearch($embedding, $conversationId, $limit);

            if (! empty($vectorResults)) {
                return $vectorResults;
            }
        }

        return $this->textSearch($query, $conversationId, $limit);
    }

    protected function vectorSearch(array $embedding, ?string $conversationId, int $limit): array
    {
        $results = $this->vectorManager->search($embedding, $limit, 0.6);

        $ids = array_column($results, 'id');
        $scores = [];

        foreach ($results as $result) {
            $scores[$result['id']] = $result['score'];
        }

        $query = AiMemory::whereIn('id', $ids);

        if ($conversationId !== null) {
            $query->where('conversation_id', $conversationId);
        }

        $memories = $query->get();

        return $memories->map(function (AiMemory $memory) use ($scores) {
            return [
                'content' => $memory->content,
                'score' => $scores[$memory->id] ?? 0,
            ];
        })->sortByDesc('score')->values()->toArray();
    }

    protected function textSearch(string $query, ?string $conversationId, int $limit): array
    {
        $dbQuery = AiMemory::where('content', 'LIKE', "%{$query}%");

        if ($conversationId !== null) {
            $dbQuery->where('conversation_id', $conversationId);
        }

        $memories = $dbQuery->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $memories->map(function (AiMemory $memory) use ($query) {
            return [
                'content' => $memory->content,
                'score' => $this->calculateScore($memory->content, $query),
            ];
        })->sortByDesc('score')->values()->toArray();
    }

    protected function calculateScore(string $content, string $query): float
    {
        $contentLower = mb_strtolower($content);
        $queryLower = mb_strtolower($query);
        $words = explode(' ', $queryLower);
        $matches = 0;

        foreach ($words as $word) {
            if (str_contains($contentLower, $word)) {
                $matches++;
            }
        }

        return count($words) > 0 ? $matches / count($words) : 0;
    }
}
