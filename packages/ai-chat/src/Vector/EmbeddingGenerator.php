<?php

namespace AiChat\Vector;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\AiManager;

class EmbeddingGenerator
{
    protected bool $cachingEnabled;

    protected string $cacheStore;

    protected int $dimensions;

    public function __construct()
    {
        $this->cachingEnabled = config('ai-chat.caching.embeddings.cache', false);
        $this->cacheStore = config('ai-chat.caching.embeddings.store', 'database');
        $this->dimensions = (int) config('ai-chat.vector.dimensions', 1536);
    }

    public function generate(string $text): array
    {
        if ($this->cachingEnabled) {
            $cacheKey = $this->cacheKey($text);

            $cached = Cache::store($this->cacheStore)->get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }
        }

        $embedding = $this->generateFromProvider($text) ?? $this->generateFallback($text);

        if ($this->cachingEnabled) {
            Cache::store($this->cacheStore)->put($this->cacheKey($text), $embedding, now()->addDays(30));
        }

        return $embedding;
    }

    public function generateBatch(array $texts): array
    {
        $embeddings = [];

        foreach ($texts as $text) {
            $embeddings[] = $this->generate($text);
        }

        return $embeddings;
    }

    protected function generateFromProvider(string $text): ?array
    {
        try {
            $driver = config('ai-chat.default_for_embeddings', 'openai');

            $aiManager = app(AiManager::class);

            try {
                $provider = $aiManager->fakeableEmbeddingProvider($driver);

                $result = $provider->embeddings([$text], $this->dimensions);

                if ($result !== null) {
                    return $result->first();
                }
            } catch (\LogicException $e) {
                Log::debug('Embedding provider not available for driver, using fallback', [
                    'driver' => $driver,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('Embedding provider failed, using fallback', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function generateFallback(string $text): array
    {
        $hash = hash('sha256', $text, true);
        $vector = [];

        for ($i = 0; $i < $this->dimensions; $i++) {
            $byteIndex = $i % strlen($hash);
            $vector[] = (ord($hash[$byteIndex]) - 128) / 128;
        }

        $magnitude = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));

        if ($magnitude > 0) {
            $vector = array_map(fn ($v) => $v / $magnitude, $vector);
        }

        return $vector;
    }

    protected function cacheKey(string $text): string
    {
        return 'ai-chat:embedding:'.md5($text);
    }
}
