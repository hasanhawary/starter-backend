<?php

namespace AiChat\Search;

use AiChat\Models\AiKnowledgeChunk;
use AiChat\Vector\EmbeddingGenerator;
use AiChat\Vector\VectorManager;

class KnowledgeSearch
{
    public function __construct(
        protected EmbeddingGenerator $embeddings,
        protected VectorManager $vectorManager,
    ) {}

    public function search(string $query, int $limit = 5): array
    {
        $embedding = $this->embeddings->generate($query);

        if (! empty(array_filter($embedding))) {
            $vectorResults = $this->vectorSearch($embedding, $limit);

            if (! empty($vectorResults)) {
                return $vectorResults;
            }
        }

        return $this->textSearch($query, $limit);
    }

    protected function vectorSearch(array $embedding, int $limit): array
    {
        $results = $this->vectorManager->search($embedding, $limit, 0.5);

        if (empty($results)) {
            return [];
        }

        $ids = array_column($results, 'id');
        $scores = [];

        foreach ($results as $result) {
            $scores[$result['id']] = $result['score'];
        }

        $chunks = AiKnowledgeChunk::with('document')
            ->whereIn('id', $ids)
            ->get();

        return $chunks->map(function ($chunk) use ($scores) {
            return [
                'content' => $chunk->content,
                'source' => $chunk->document?->source_path ?? 'unknown',
                'score' => $scores[$chunk->id] ?? 0,
            ];
        })->sortByDesc('score')->values()->toArray();
    }

    protected function textSearch(string $query, int $limit): array
    {
        $words = explode(' ', $query);

        $q = AiKnowledgeChunk::with('document');

        $q->where(function ($queryBuilder) use ($words) {
            foreach ($words as $word) {
                $queryBuilder->orWhere('content', 'LIKE', "%{$word}%");
            }
        });

        $chunks = $q->limit($limit)->get();

        return $chunks->map(function (AiKnowledgeChunk $chunk) use ($query) {
            return [
                'content' => $chunk->content,
                'source' => $chunk->document?->source_path ?? 'unknown',
                'score' => $this->calculateScore($chunk->content, $query),
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

        $score = count($words) > 0 ? $matches / count($words) : 0;

        if (str_contains($contentLower, $queryLower)) {
            $score = min($score + 0.2, 1.0);
        }

        return $score;
    }
}
