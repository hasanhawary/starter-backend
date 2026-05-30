<?php

namespace AiChat\Vector\Drivers;

use AiChat\Contracts\VectorStoreInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QdrantDriver implements VectorStoreInterface
{
    protected string $url;

    protected string $apiKey;

    protected string $collection;

    protected int $timeout;

    public function __construct(array $config = [])
    {
        $this->url = $config['url'] ?? env('QDRANT_URL', 'http://localhost:6333');
        $this->apiKey = $config['api_key'] ?? env('QDRANT_API_KEY', '');
        $this->collection = $config['collection'] ?? 'ai_chat_vectors';
        $this->timeout = $config['timeout'] ?? 10;
    }

    public function index(string $id, array $embedding, array $metadata = []): bool
    {
        try {
            $response = $this->http()->put("{$this->url}/collections/{$this->collection}/points", [
                'points' => [
                    [
                        'id' => $id,
                        'vector' => $embedding,
                        'payload' => $metadata,
                    ],
                ],
            ]);

            return $response->successful();
        } catch (ConnectionException $e) {
            Log::error('Qdrant index failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.7): array
    {
        try {
            $response = $this->http()->post("{$this->url}/collections/{$this->collection}/points/search", [
                'vector' => $embedding,
                'limit' => $limit,
                'score_threshold' => $threshold,
                'with_payload' => true,
            ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('result', []))->map(function ($hit) {
                return [
                    'id' => $hit['id'],
                    'score' => $hit['score'],
                    'metadata' => $hit['payload'] ?? [],
                ];
            })->toArray();
        } catch (ConnectionException $e) {
            Log::error('Qdrant search failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function delete(string $id): bool
    {
        try {
            $response = $this->http()->post("{$this->url}/collections/{$this->collection}/points/delete", [
                'points' => [$id],
            ]);

            return $response->successful();
        } catch (ConnectionException $e) {
            Log::error('Qdrant delete failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function deleteAll(): bool
    {
        try {
            $response = $this->http()->post("{$this->url}/collections/{$this->collection}/points/delete", [
                'filter' => ['must' => []],
            ]);

            return $response->successful();
        } catch (ConnectionException $e) {
            Log::error('Qdrant deleteAll failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    protected function http()
    {
        $client = Http::timeout($this->timeout);

        if ($this->apiKey !== '') {
            $client = $client->withHeaders(['api-key' => $this->apiKey]);
        }

        return $client;
    }
}
