<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Agents\ChatAgent;
use AiChat\Pipeline\ChatPayload;
use Closure;

class SendToProvider
{
    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if (! $payload->agent) {
            $payload->addError('AI provider is not configured. Please set up an AI provider first.', 503);

            return $payload;
        }

        $chatAgent = $this->buildChatAgent($payload);

        if ($payload->streaming) {
            return $this->handleStreaming($payload, $chatAgent, $next);
        }

        return $this->handleSync($payload, $chatAgent, $next);
    }

    protected function buildChatAgent(ChatPayload $payload): ChatAgent
    {
        $systemPrompt = $this->resolveSystemPrompt($payload);

        $agent = new ChatAgent($systemPrompt);

        $sessionId = $payload->metadata['session_id'] ?? null;
        $conversationId = $payload->conversationId();

        if ($conversationId && $sessionId) {
            $agent->continue($conversationId, $sessionId);
        } elseif ($sessionId) {
            $agent->forSession($sessionId);
        }

        if (! empty($payload->tools)) {
            $agent->withTools(array_keys($payload->tools));
        }

        if ($payload->executionPlan) {
            $agent->withHistoryLimit($payload->executionPlan->historyLimit);
        }

        return $agent;
    }

    protected function resolveSystemPrompt(ChatPayload $payload): string
    {
        $parts = [];
        $agentPrompt = $payload->agent?->systemPrompt() ?? config('ai-chat.conversations.default_system_prompt', '');
        $plan = $payload->executionPlan;

        if ($agentPrompt !== '') {
            $parts[] = $agentPrompt;
        }

        if ($plan) {
            if ($plan->intent === 'direct' && $plan->historyLimit === 0) {
                $parts[] = "\n\nThis is a greeting or casual message. Reply naturally and briefly to the current message only. Do not summarize, repeat, or answer previous unrelated questions unless the user explicitly asks.";
            } elseif ($plan->isSimpleLiveData()) {
                $parts[] = "\n\nUse the available tools to answer this live-data question. Do not invent values.";
            } elseif ($plan->isKnowledgeRequest()) {
                $parts[] = "\n\nAnswer only from retrieved project knowledge. If missing, say you do not have enough information.";
            } elseif ($plan->isMemoryRequest()) {
                $parts[] = "\n\nUse the conversation memory context to provide a relevant response.";
            }
        }

        if (! empty($payload->context)) {
            $parts[] = "\n\n## Context\n".json_encode($payload->context, JSON_PRETTY_PRINT);
        }

        if (! empty($payload->knowledge)) {
            $parts[] = "\n\n## Knowledge Base\n".collect($payload->knowledge)
                ->map(fn ($k, $i) => '['.($i + 1).'] '.(is_array($k) ? json_encode($k) : (string) $k))
                ->implode("\n");
        }

        if (! empty($payload->memory)) {
            $parts[] = "\n\n## Conversation Memory\n".collect($payload->memory)
                ->map(fn ($m) => is_array($m) ? json_encode($m) : (string) $m)
                ->implode("\n");
        }

        return implode('', $parts);
    }

    protected function handleSync(ChatPayload $payload, ChatAgent $chatAgent, Closure $next): ChatPayload
    {
        try {
            $response = $chatAgent->prompt($payload->message);
        } catch (\Throwable $e) {
            $payload->addError('AI provider error: '.$e->getMessage(), 503);

            return $payload;
        }

        $payload->response = $response->text ?? (string) $response;

        if (isset($response->usage)) {
            $payload->setMetadata('usage', (array) $response->usage);
        }

        if ($response->toolCalls ?? false) {
            $payload->rawResponse = [
                'tool_calls' => $response->toolCalls->map(fn ($tc) => [
                    'id' => $tc->id,
                    'name' => $tc->name,
                    'arguments' => $tc->arguments,
                ])->toArray(),
            ];
        }

        return $next($payload);
    }

    protected function handleStreaming(ChatPayload $payload, ChatAgent $chatAgent, Closure $next): ChatPayload
    {
        try {
            $stream = $chatAgent->stream($payload->message);
            $fullContent = '';

            foreach ($stream as $event) {
                $content = (string) ($event->text ?? '');
                $fullContent .= $content;
            }

            $payload->response = $fullContent;
            $payload->setMetadata('streamed', true);

            return $next($payload);
        } catch (\Throwable $e) {
            $payload->addError('AI provider error: '.$e->getMessage(), 503);

            return $payload;
        }
    }
}
