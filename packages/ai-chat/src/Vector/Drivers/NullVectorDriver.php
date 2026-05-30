<?php

namespace AiChat\Vector\Drivers;

use AiChat\Contracts\VectorStoreInterface;

class NullVectorDriver implements VectorStoreInterface
{
    public function index(string $id, array $embedding, array $metadata = []): bool
    {
        return false;
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.7): array
    {
        return [];
    }

    public function delete(string $id): bool
    {
        return false;
    }

    public function deleteAll(): bool
    {
        return false;
    }
}
