<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Contracts\VectorStoreInterface;
use AiChat\Pipeline\ChatPayload;
use AiChat\Support\TokenCounter;
use Closure;
use Illuminate\Support\Facades\App;
use Laravel\Ai\AiManager;

class RetrieveKnowledge
{
    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if (! config('ai-chat.rag.enabled', false)) {
            return $next($payload);
        }

        if ($payload->executionPlan && ! $payload->executionPlan->useRag) {
            return $next($payload);
        }

        $storeClass = config('ai-chat.rag.vector_store');

        if (! $storeClass || ! class_exists($storeClass)) {
            return $next($payload);
        }

        try {
            $store = App::make($storeClass);

            if (! ($store instanceof VectorStoreInterface)) {
                return $next($payload);
            }

            $embedding = $this->generateEmbedding($payload->message);

            if ($embedding === null) {
                return $next($payload);
            }

            $query = $payload->executionPlan?->ragQuery ?? $payload->message;
            $limit = (int) ($payload->executionPlan?->ragLimit ?? config('ai-chat.rag.max_results', 5));

            $results = $store->search($embedding, $limit);

            $budget = (int) config('ai-chat.rag.token_budget', 2000);
            $used = 0;

            foreach ($results as $result) {
                $content = is_array($result) ? json_encode($result) : (string) $result;
                $tokens = TokenCounter::estimate($content);

                if ($used + $tokens > $budget) {
                    break;
                }

                $payload->knowledge[] = $result;
                $used += $tokens;
            }
        } catch (\Throwable) {
            return $next($payload);
        }

        return $next($payload);
    }

    protected function generateEmbedding(string $text): ?array
    {
        try {
            $embeddingDriver = app(AiManager::class)->driver(config('ai-chat.default_for_embeddings', 'openai'));

            $response = $embeddingDriver->embed($text);

            return $response;
        } catch (\Throwable) {
            return null;
        }
    }
}
