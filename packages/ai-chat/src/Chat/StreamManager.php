<?php

namespace AiChat\Chat;

use Illuminate\Support\Facades\Cache;

class StreamManager
{
    protected function key(string $conversationId): string
    {
        return "ai-chat:stream:{$conversationId}";
    }

    protected function contentKey(string $conversationId): string
    {
        return "ai-chat:stream:content:{$conversationId}";
    }

    protected function ttl(): int
    {
        return (int) config('ai-chat.streaming.ttl', 300);
    }

    public function startStream(string $conversationId): void
    {
        Cache::put($this->key($conversationId), true, $this->ttl());
        Cache::put($this->contentKey($conversationId), '', $this->ttl());
    }

    public function appendToStream(string $conversationId, string $chunk): void
    {
        if (! $this->isStreaming($conversationId)) {
            return;
        }

        $existing = Cache::get($this->contentKey($conversationId), '');
        Cache::put($this->contentKey($conversationId), $existing.$chunk, $this->ttl());
    }

    public function endStream(string $conversationId, string $fullContent): void
    {
        Cache::forget($this->key($conversationId));
        Cache::forget($this->contentKey($conversationId));
    }

    public function isStreaming(string $conversationId): bool
    {
        return Cache::get($this->key($conversationId), false) === true;
    }

    public function abortStream(string $conversationId): void
    {
        Cache::forget($this->key($conversationId));
        Cache::forget($this->contentKey($conversationId));
    }
}
