<?php

namespace AiChat\Vector\Drivers;

use AiChat\Contracts\VectorStoreInterface;
use Illuminate\Support\Facades\DB;

class PgVectorDriver implements VectorStoreInterface
{
    protected string $connection;

    protected string $table;

    public function __construct(array $config = [])
    {
        $this->connection = $config['connection'] ?? config('database.default');
        $this->table = $config['table'] ?? 'ai_vectors';
    }

    public function index(string $id, array $embedding, array $metadata = []): bool
    {
        $embeddingString = '['.implode(',', array_map(fn ($v) => (float) $v, $embedding)).']';

        DB::connection($this->connection)->table($this->table)->upsert(
            [
                'id' => $id,
                'embedding' => DB::raw("'{$embeddingString}'::vector"),
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
                'created_at' => now(),
            ],
            ['id'],
            [
                'embedding' => DB::raw("'{$embeddingString}'::vector"),
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]
        );

        return true;
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.7): array
    {
        $embeddingString = '['.implode(',', array_map(fn ($v) => (float) $v, $embedding)).']';

        $results = DB::connection($this->connection)
            ->table($this->table)
            ->select(
                'id',
                'metadata',
                DB::raw("1 - (embedding <=> '{$embeddingString}'::vector) as score")
            )
            ->whereRaw("1 - (embedding <=> '{$embeddingString}'::vector) >= ?", [$threshold])
            ->orderByRaw("embedding <=> '{$embeddingString}'::vector ASC")
            ->limit($limit)
            ->get();

        return $results->map(function ($result) {
            return [
                'id' => $result->id,
                'score' => (float) $result->score,
                'metadata' => json_decode($result->metadata, true) ?? [],
            ];
        })->toArray();
    }

    public function delete(string $id): bool
    {
        return DB::connection($this->connection)
            ->table($this->table)
            ->where('id', $id)
            ->delete() > 0;
    }

    public function deleteAll(): bool
    {
        DB::connection($this->connection)->table($this->table)->truncate();

        return true;
    }
}
