<?php

namespace AiChat\Contracts;

interface VectorStoreInterface
{
    public function index(string $id, array $embedding, array $metadata = []): bool;

    public function search(array $embedding, int $limit = 5, float $threshold = 0.7): array;

    public function delete(string $id): bool;

    public function deleteAll(): bool;
}
