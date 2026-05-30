<?php

namespace AiChat\Vector\Drivers;

use AiChat\Contracts\VectorStoreInterface;
use AiChat\Models\AiKnowledgeChunk;
use Illuminate\Support\Facades\DB;

class DatabaseVectorDriver implements VectorStoreInterface
{
    protected string $connection;

    protected string $table;

    protected int $prefetchLimit;

    public function __construct(array $config = [])
    {
        $this->connection = $config['connection'] ?? config('database.default');
        $this->table = $config['table'] ?? 'ai_knowledge_chunks';
        $this->prefetchLimit = $config['prefetch_limit'] ?? 50;
    }

    public function index(string $id, array $embedding, array $metadata = []): bool
    {
        return DB::connection($this->connection)
            ->table($this->table)
            ->where('id', $id)
            ->update([
                'embedding' => json_encode($embedding),
            ]) > 0;
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.7): array
    {
        $chunks = AiKnowledgeChunk::whereNotNull('embedding')
            ->select(['id', 'embedding', 'content'])
            ->limit($this->prefetchLimit)
            ->get();

        $scored = [];

        foreach ($chunks as $chunk) {
            $chunkEmbedding = is_array($chunk->embedding)
                ? $chunk->embedding
                : json_decode($chunk->embedding, true);

            if (! is_array($chunkEmbedding)) {
                continue;
            }

            $vectorScore = $this->cosineSimilarity($embedding, $chunkEmbedding);

            $scored[] = [
                'id' => $chunk->id,
                'score' => $vectorScore,
                'metadata' => [],
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_values(array_filter($scored, fn ($r) => $r['score'] >= $threshold)), 0, $limit);
    }

    public function delete(string $id): bool
    {
        return DB::connection($this->connection)
            ->table($this->table)
            ->where('id', $id)
            ->update(['embedding' => null]) > 0;
    }

    public function deleteAll(): bool
    {
        DB::connection($this->connection)
            ->table($this->table)
            ->update(['embedding' => null]);

        return true;
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
