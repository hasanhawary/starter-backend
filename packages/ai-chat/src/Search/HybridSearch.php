<?php

namespace AiChat\Search;

use AiChat\Vector\EmbeddingGenerator;

class HybridSearch
{
    public function __construct(
        protected SqlSearch $sqlSearch,
        protected VectorSearch $vectorSearch,
        protected KnowledgeSearch $knowledgeSearch,
        protected EmbeddingGenerator $embeddings,
    ) {}

    public function search(string $query, int $limit = 10): array
    {
        $results = [];
        $seen = [];

        $knowledgeResults = $this->knowledgeSearch->search($query, $limit);

        foreach ($knowledgeResults as $result) {
            $key = md5($result['content']);
            $seen[$key] = true;
            $results[] = [
                'content' => $result['content'],
                'source' => $result['source'] ?? 'knowledge',
                'score' => ($result['score'] ?? 0.5) * 0.4,
                'type' => 'knowledge',
            ];
        }

        $embedding = $this->embeddings->generate($query);

        if (! empty(array_filter($embedding))) {
            $vectorResults = $this->vectorSearch->search($embedding, $limit);

            foreach ($vectorResults as $result) {
                $key = md5(json_encode($result));
                $boost = isset($seen[$key]) ? 0.1 : 0.3;
                $seen[$key] = true;

                $results[] = [
                    'content' => $result['content'] ?? json_encode($result),
                    'source' => $result['metadata']['source_path'] ?? 'vector',
                    'score' => ($result['score'] ?? 0.5) * $boost,
                    'type' => 'vector',
                ];
            }
        }

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($this->deduplicate($results), 0, $limit);
    }

    protected function deduplicate(array $results): array
    {
        $seen = [];
        $deduplicated = [];

        foreach ($results as $result) {
            $key = md5($result['content']);

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $deduplicated[] = $result;
            }
        }

        return $deduplicated;
    }
}
