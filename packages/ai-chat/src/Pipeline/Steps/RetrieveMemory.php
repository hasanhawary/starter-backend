<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Contracts\MemoryStoreInterface;
use AiChat\Pipeline\ChatPayload;
use AiChat\Support\TokenCounter;
use Closure;
use Illuminate\Support\Facades\App;
use Laravel\Ai\AiManager;

class RetrieveMemory
{
    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if (! config('ai-chat.memory.enabled', false)) {
            return $next($payload);
        }

        $storeClass = config('ai-chat.memory.store');

        if (! $storeClass || ! class_exists($storeClass)) {
            return $next($payload);
        }

        $conversationId = $payload->conversationId();

        if (! $conversationId) {
            return $next($payload);
        }

        try {
            $store = App::make($storeClass);

            if (! ($store instanceof MemoryStoreInterface)) {
                return $next($payload);
            }

            $embedding = $this->generateEmbedding($payload->message);

            $limit = (int) config('ai-chat.memory.max_results', 5);

            $results = $store->retrieve($conversationId, $embedding ?? [], $limit);

            $budget = (int) config('ai-chat.memory.token_budget', 1000);
            $used = 0;

            foreach ($results as $result) {
                $content = is_array($result) ? json_encode($result) : (string) $result;
                $tokens = TokenCounter::estimate($content);

                if ($used + $tokens > $budget) {
                    break;
                }

                $payload->memory[] = $result;
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
            $response = app(AiManager::class)
                ->driver(config('ai-chat.default_for_embeddings', 'openai'))
                ->embed($text);

            return $response;
        } catch (\Throwable) {
            return null;
        }
    }
}
