<?php

namespace AiChat\Vector;

use AiChat\Contracts\VectorStoreInterface;
use AiChat\Vector\Drivers\NullVectorDriver;

class VectorManager
{
    protected VectorStoreInterface $driver;

    public function __construct(?string $driverName = null)
    {
        $this->driver = $this->resolveDriver($driverName);
    }

    public function index(string $id, array $embedding, array $metadata = []): bool
    {
        return $this->driver->index($id, $embedding, $metadata);
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.7): array
    {
        return $this->driver->search($embedding, $limit, $threshold);
    }

    public function delete(string $id): bool
    {
        return $this->driver->delete($id);
    }

    public function deleteAll(): bool
    {
        return $this->driver->deleteAll();
    }

    public function getDriver(): VectorStoreInterface
    {
        return $this->driver;
    }

    protected function resolveDriver(?string $driverName): VectorStoreInterface
    {
        $name = $driverName ?? config('ai-chat.vector.driver', 'null');

        return match ($name) {
            'database' => new Drivers\DatabaseVectorDriver([
                'connection' => config('ai-chat.vector.database.connection', config('database.default')),
                'table' => config('ai-chat.vector.database.table', 'ai_knowledge_chunks'),
            ]),
            'pgvector' => new Drivers\PgVectorDriver([
                'connection' => config('ai-chat.vector.pgvector.connection', 'pgsql'),
                'table' => config('ai-chat.vector.pgvector.table', 'ai_vectors'),
            ]),
            'qdrant' => new Drivers\QdrantDriver([
                'url' => config('ai-chat.vector.qdrant.url', 'http://localhost:6333'),
                'api_key' => config('ai-chat.vector.qdrant.api_key'),
                'collection' => config('ai-chat.vector.qdrant.collection', 'ai_chat'),
            ]),
            'pinecone' => new Drivers\PineconeDriver([
                'api_key' => config('ai-chat.vector.pinecone.api_key'),
                'environment' => config('ai-chat.vector.pinecone.environment'),
                'index' => config('ai-chat.vector.pinecone.index', 'ai-chat'),
            ]),
            'null' => new NullVectorDriver,
            default => new NullVectorDriver,
        };
    }
}
