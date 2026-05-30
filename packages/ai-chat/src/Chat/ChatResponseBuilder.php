<?php

namespace AiChat\Chat;

use AiChat\Models\AiChatConversation;
use AiChat\Models\AiChatMessage;

class ChatResponseBuilder
{
    public function buildResponse(AiChatMessage $message, AiChatConversation $conversation): array
    {
        return [
            'message' => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'tool_calls' => $message->tool_calls,
                'tool_results' => $message->tool_results,
                'usage' => $message->usage,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            ],
        ];
    }

    public function buildStreamChunk(string $content, bool $done = false): array
    {
        return [
            'chunk' => $content,
            'done' => $done,
        ];
    }

    public function buildErrorResponse(string $error, int $code = 500): array
    {
        return [
            'error' => [
                'message' => $error,
                'code' => $code,
            ],
        ];
    }

    public function buildToolCallResponse(array $toolCalls): array
    {
        return [
            'tool_calls' => $toolCalls,
        ];
    }
}
