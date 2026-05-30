<?php

namespace AiChat\Vector\Drivers;

use AiChat\Contracts\VectorStoreInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PineconeDriver implements VectorStoreInterface
{
    protected string $apiKey;

    protected string $environment;

    protected string $index;

    protected int $timeout;

    public function __construct(array $config = [])
    {
        $this->apiKey = $config['api_key'] ?? env('PINECONE_API_KEY', '');
        $this->environment = $config['environment'] ?? env('PINECONE_ENVIRONMENT', 'us-east-1');
        $this->index = $config['index'] ?? env('PINECONE_INDEX', 'ai-chat');
        $this->timeout = $config['timeout'] ?? 10;
    }

    public function index(string $id, array $embedding, array $metadata = []): bool
    {
        try {
            $response = $this->http()->post($this->vectorsUrl().'/upsert', [
                'vectors' => [
                    [
                        'id' => $id,
                        'values' => $embedding,
                        'metadata' => $metadata,
                    ],
                ],
            ]);

            return $response->successful();
        } catch (ConnectionException $e) {
            Log::error('Pinecone index failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.7): array
    {
        try {
            $response = $this->http()->post($this->vectorsUrl().'/query', [
                'vector' => $embedding,
                'topK' => $limit,
                'includeMetadata' => true,
            ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('matches', []))
                ->filter(fn ($match) => ($match['score'] ?? 0) >= $threshold)
                ->map(function ($match) {
                    return [
                        'id' => $match['id'],
                        'score' => $match['score'],
                        'metadata' => $match['metadata'] ?? [],
                    ];
                })->values()->toArray();
        } catch (ConnectionException $e) {
            Log::error('Pinecone search failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function delete(string $id): bool
    {
        try {
            $response = $this->http()->post($this->vectorsUrl().'/delete', [
                'ids' => [$id],
            ]);

            return $response->successful();
        } catch (ConnectionException $e) {
            Log::error('Pinecone delete failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function deleteAll(): bool
    {
        try {
            $response = $this->http()->post($this->vectorsUrl().'/delete', [
                'deleteAll' => true,
            ]);

            return $response->successful();
        } catch (ConnectionException $e) {
            Log::error('Pinecone deleteAll failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    protected function http()
    {
        return Http::timeout($this->timeout)
            ->withHeaders([
                'Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ]);
    }

    protected function vectorsUrl(): string
    {
        return "https://{$this->index}-{$this->environment}.svc.pinecone.io";
    }
}
