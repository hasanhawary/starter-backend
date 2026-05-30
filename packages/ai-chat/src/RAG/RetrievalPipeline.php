<?php

namespace AiChat\RAG;

use AiChat\Models\AiKnowledgeChunk;
use AiChat\Vector\EmbeddingGenerator;
use AiChat\Vector\VectorManager;

class RetrievalPipeline
{
    public function __construct(
        protected EmbeddingGenerator $embeddings,
        protected VectorManager $vectorManager,
    ) {}

    public function retrieve(string $query, int $limit = 5): array
    {
        $embedding = $this->embeddings->generate($query);

        if (! empty(array_filter($embedding))) {
            return $this->vectorSearch($embedding, $limit);
        }

        return $this->textSearch($query, $limit);
    }

    protected function vectorSearch(array $embedding, int $limit): array
    {
        $results = $this->vectorManager->search($embedding, $limit, 0.5);

        if (! empty($results)) {
            return array_map(function ($result) {
                $chunk = AiKnowledgeChunk::with('document')->find($result['id'] ?? null);

                return [
                    'content' => $chunk?->content ?? $result['content'] ?? '',
                    'source' => $chunk?->document?->source_path ?? $result['metadata']['source_path'] ?? 'unknown',
                    'score' => $result['score'] ?? 0,
                ];
            }, $results);
        }

        return [];
    }

    protected function textSearch(string $query, int $limit): array
    {
        $chunks = AiKnowledgeChunk::with('document')
            ->where('content', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->get();

        return $chunks->map(function (AiKnowledgeChunk $chunk) use ($query) {
            return [
                'content' => $chunk->content,
                'source' => $chunk->document?->source_path ?? 'unknown',
                'score' => $this->calculateTextScore($chunk->content, $query),
            ];
        })->sortByDesc('score')->values()->toArray();
    }

    protected function calculateTextScore(string $content, string $query): float
    {
        $contentLower = mb_strtolower($content);
        $queryLower = mb_strtolower($query);
        $queryWords = explode(' ', $queryLower);
        $matchCount = 0;

        foreach ($queryWords as $word) {
            if (str_contains($contentLower, $word)) {
                $matchCount++;
            }
        }

        return count($queryWords) > 0 ? $matchCount / count($queryWords) : 0;
    }
}
