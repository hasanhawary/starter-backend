<?php

namespace AiChat\Contracts;

interface MemoryStoreInterface
{
    public function store(string $conversationId, string $content, array $embedding): bool;

    public function retrieve(string $conversationId, array $embedding, int $limit = 5): array;

    public function forget(string $conversationId): bool;
}
