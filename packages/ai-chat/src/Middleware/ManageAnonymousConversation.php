<?php

namespace AiChat\Middleware;

use Closure;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Prompts\AgentPrompt;

class ManageAnonymousConversation
{
    public function __construct(protected ConversationStore $store) {}

    public function handle(AgentPrompt $prompt, Closure $next)
    {
        $agent = $prompt->agent;
        $sessionId = method_exists($agent, 'sessionId') ? $agent->sessionId() : null;

        if (! $sessionId) {
            return $next($prompt);
        }

        $conversationId = $agent->currentConversation();

        if (! $conversationId) {
            $conversationId = $this->store->storeConversation($sessionId, 'New Chat');

            if (method_exists($agent, 'continue')) {
                $agent->continue($conversationId, $sessionId);
            }
        }

        $this->store->storeUserMessage($conversationId, $sessionId, $prompt);

        $response = $next($prompt);

        if (method_exists($response, 'conversationId') && $response->conversationId) {
            $this->store->storeAssistantMessage($conversationId, $sessionId, $prompt, $response);
        } elseif (method_exists($response, 'text')) {
            $this->store->storeAssistantMessage($conversationId, $sessionId, $prompt, $response);
        }

        return $response;
    }
}
