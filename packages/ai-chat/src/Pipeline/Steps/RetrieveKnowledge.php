<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Pipeline\ChatPayload;
use AiChat\RAG\RetrievalPipeline;
use AiChat\Support\TokenCounter;
use Closure;

class RetrieveKnowledge
{
    public function __construct(
        protected RetrievalPipeline $retrievalPipeline,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if (! config('ai-chat.knowledge.enabled', false)) {
            return $next($payload);
        }

        if ($payload->executionPlan && ! $payload->executionPlan->useRag) {
            return $next($payload);
        }

        try {
            $query = $payload->executionPlan?->ragQuery ?? $payload->message;
            $limit = (int) ($payload->executionPlan?->ragLimit ?? config('ai-chat.context.rag_limit', 5));

            $results = $this->retrievalPipeline->retrieve($query, $limit);

            $budget = (int) config('ai-chat.knowledge.token_budget', 2000);
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
}
