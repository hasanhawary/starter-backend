<?php

namespace AiChat\Vector;

use AiChat\Contracts\VectorStoreInterface;
use AiChat\Vector\Drivers\NullVectorDriver;
use Illuminate\Support\Facades\App;

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
        $config = config("ai-chat.vector.drivers.{$name}", []);

        return match ($name) {
            'pgvector' => App::make(Drivers\PgVectorDriver::class, ['config' => $config]),
            'qdrant' => App::make(Drivers\QdrantDriver::class, ['config' => $config]),
            'pinecone' => App::make(Drivers\PineconeDriver::class, ['config' => $config]),
            'null' => new NullVectorDriver,
            default => new NullVectorDriver,
        };
    }
}
