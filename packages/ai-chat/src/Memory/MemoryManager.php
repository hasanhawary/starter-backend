<?php

namespace AiChat\Memory;

use AiChat\Contracts\MemoryStoreInterface;
use AiChat\Models\AiMemory;
use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Support\Str;

class MemoryManager
{
    protected MemoryStoreInterface $store;

    public function __construct(
        protected EmbeddingGenerator $embeddings,
    ) {
        $storeClass = config('ai-chat.memory.store');

        $this->store = $storeClass && class_exists($storeClass)
            ? app($storeClass)
            : app(MemoryStoreInterface::class);
    }

    public function store(string $conversationId, string $content, ?string $userId = null): AiMemory
    {
        $embedding = $this->generateEmbedding($content);

        $memory = AiMemory::create([
            'id' => Str::uuid()->toString(),
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'content' => $content,
            'embedding' => $embedding,
            'metadata' => [
                'user_id' => $userId,
                'stored_at' => now()->toIso8601String(),
            ],
        ]);

        $this->store->store($conversationId, $content, $embedding);

        return $memory;
    }

    public function retrieve(string $conversationId, string $query, int $limit = 5): array
    {
        $embedding = $this->generateEmbedding($query);

        return $this->store->retrieve($conversationId, $embedding, $limit);
    }

    public function forget(string $conversationId): bool
    {
        AiMemory::where('conversation_id', $conversationId)->delete();

        return $this->store->forget($conversationId);
    }

    public function generateEmbedding(string $content): array
    {
        return $this->embeddings->generate($content);
    }
}
