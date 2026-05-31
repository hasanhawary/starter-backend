<?php

namespace AiChat\Memory\Stores;

use AiChat\Contracts\MemoryStoreInterface;
use AiChat\Models\AiMemory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DatabaseMemoryStore implements MemoryStoreInterface
{
    public function store(string $conversationId, string $content, array $embedding): bool
    {
        try {
            AiMemory::create([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversationId,
                'content' => $content,
                'embedding' => $embedding,
                'metadata' => [],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('DatabaseMemoryStore: failed to store memory', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
            ]);

            return false;
        }
    }

    public function retrieve(string $conversationId, array $embedding, int $limit = 5): array
    {
        $memories = AiMemory::whereNotNull('embedding')
            ->where('conversation_id', $conversationId)
            ->select(['id', 'content', 'embedding', 'metadata'])
            ->limit(50)
            ->get();

        $scored = [];

        foreach ($memories as $memory) {
            $memoryEmbedding = is_array($memory->embedding)
                ? $memory->embedding
                : json_decode($memory->embedding, true);

            if (! is_array($memoryEmbedding)) {
                continue;
            }

            $score = $this->cosineSimilarity($embedding, $memoryEmbedding);

            $scored[] = [
                'id' => $memory->id,
                'content' => $memory->content,
                'score' => $score,
                'metadata' => $memory->metadata ?? [],
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_values(array_filter($scored, fn ($r) => $r['score'] >= 0.6)), 0, $limit);
    }

    public function forget(string $conversationId): bool
    {
        AiMemory::where('conversation_id', $conversationId)->delete();

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
