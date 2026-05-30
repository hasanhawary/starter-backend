<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Chat\ConversationManager;
use AiChat\Chat\MessageManager;
use AiChat\Pipeline\ChatPayload;
use Closure;
use Illuminate\Support\Facades\DB;

class PersistResponse
{
    public function __construct(
        protected MessageManager $messageManager,
        protected ConversationManager $conversationManager,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if ($payload->response === null && empty($payload->toolResults)) {
            return $next($payload);
        }

        $conversationId = $payload->conversationId();

        if (! $conversationId) {
            $userId = $payload->user?->getAuthIdentifier();
            $conversation = $this->conversationManager->create(
                is_string($userId) ? $userId : (string) $userId,
                'New Chat',
            );
            $payload->conversation = $conversation;
            $conversationId = $conversation->id;
        }

        DB::transaction(function () use ($payload, $conversationId): void {
            $this->persistUserMessage($payload, $conversationId);

            $this->persistAssistantResponse($payload, $conversationId);
        });

        return $next($payload);
    }

    protected function persistUserMessage(ChatPayload $payload, string $conversationId): void
    {
        $userId = $payload->user?->getAuthIdentifier();

        $this->messageManager->storeUserMessage(
            $conversationId,
            $payload->message,
            is_string($userId) ? $userId : (string) $userId,
            $payload->metadata,
        );
    }

    protected function persistAssistantResponse(ChatPayload $payload, string $conversationId): void
    {
        if (! empty($payload->toolResults)) {
            $toolCalls = $payload->rawResponse['tool_calls'] ?? [];
            $toolResults = array_map(fn ($result) => $result->toArray(), $payload->toolResults);

            $this->messageManager->storeToolCallMessage($conversationId, $toolCalls, $toolResults);

            return;
        }

        if ($payload->response !== null) {
            $metadata = [];

            if (isset($payload->metadata['usage'])) {
                $metadata['usage'] = $payload->metadata['usage'];
            }

            $this->messageManager->storeAssistantMessage(
                $conversationId,
                $payload->response,
                $metadata,
            );
        }
    }
}
