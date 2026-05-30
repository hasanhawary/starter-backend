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
        $hasEmbedding = ! empty(array_filter($embedding));

        $vectorResults = [];
        $textResults = [];

        if ($hasEmbedding) {
            $vectorResults = $this->vectorSearch($embedding, $limit);
        }

        $textResults = $this->textSearch($query, $limit);

        if (empty($vectorResults)) {
            return $textResults;
        }

        return $this->mergeResults($vectorResults, $textResults, $limit);
    }

    protected function vectorSearch(array $embedding, int $limit): array
    {
        $results = $this->vectorManager->search($embedding, $limit, 0.1);

        if (empty($results)) {
            return [];
        }

        $enriched = [];

        foreach ($results as $result) {
            $chunk = AiKnowledgeChunk::with('document')->find($result['id'] ?? null);

            if ($chunk) {
                $enriched[] = [
                    'content' => $chunk->content,
                    'source' => $chunk->document?->source_path ?? 'unknown',
                    'score' => $result['score'] ?? 0,
                    'type' => 'vector',
                ];
            }
        }

        return $enriched;
    }

    protected function textSearch(string $query, int $limit): array
    {
        $queryWords = array_filter(explode(' ', mb_strtolower($query)), fn ($w) => strlen($w) > 2);

        $chunks = AiKnowledgeChunk::with('document')
            ->where(function ($q) use ($query, $queryWords) {
                $q->where('content', 'LIKE', "%{$query}%");

                foreach ($queryWords as $word) {
                    $q->orWhere('content', 'LIKE', "%{$word}%");
                }
            })
            ->limit($limit * 2)
            ->get();

        return $chunks->map(function (AiKnowledgeChunk $chunk) use ($query) {
            return [
                'content' => $chunk->content,
                'source' => $chunk->document?->source_path ?? 'unknown',
                'score' => $this->calculateTextScore($chunk->content, $query),
                'type' => 'text',
            ];
        })->sortByDesc('score')->take($limit)->values()->toArray();
    }

    protected function mergeResults(array $vectorResults, array $textResults, int $limit): array
    {
        $merged = [];
        $seen = [];

        foreach ($vectorResults as $result) {
            $key = md5($result['content']);
            $seen[$key] = true;
            $merged[] = [
                'content' => $result['content'],
                'source' => $result['source'],
                'score' => $result['score'] * 0.6,
                'type' => 'vector',
            ];
        }

        foreach ($textResults as $result) {
            $key = md5($result['content']);

            if (isset($seen[$key])) {
                $existingIndex = array_search($key, array_map(fn ($r) => md5($r['content']), $merged));
                if ($existingIndex !== false) {
                    $merged[$existingIndex]['score'] = max(
                        $merged[$existingIndex]['score'],
                        $result['score'] * 0.4 + $merged[$existingIndex]['score']
                    );
                    $merged[$existingIndex]['type'] = 'hybrid';
                }

                continue;
            }

            $seen[$key] = true;
            $merged[] = [
                'content' => $result['content'],
                'source' => $result['source'],
                'score' => $result['score'] * 0.4,
                'type' => 'text',
            ];
        }

        usort($merged, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($merged, 0, $limit);
    }

    protected function calculateTextScore(string $content, string $query): float
    {
        $contentLower = mb_strtolower($content);
        $queryLower = mb_strtolower($query);
        $queryWords = array_filter(explode(' ', $queryLower), fn ($w) => strlen($w) > 2);
        $matchCount = 0;

        foreach ($queryWords as $word) {
            if (str_contains($contentLower, $word)) {
                $matchCount++;
            }
        }

        $wordScore = count($queryWords) > 0 ? $matchCount / count($queryWords) : 0;

        $fullMatchBonus = str_contains($contentLower, $queryLower) ? 0.3 : 0;

        return min(1.0, $wordScore + $fullMatchBonus);
    }
}
