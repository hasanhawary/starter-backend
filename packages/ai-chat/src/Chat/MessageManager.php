<?php

namespace AiChat\Chat;

use AiChat\Models\AiChatMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MessageManager
{
    public function storeUserMessage(string $conversationId, string $content, ?string $userId = null, array $metadata = []): AiChatMessage
    {
        $message = AiChatMessage::create([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversationId,
            'session_id' => $userId,
            'agent' => $metadata['agent'] ?? config('ai-chat.default_agent', 'project_assistant'),
            'role' => 'user',
            'content' => $content,
            'meta' => $metadata,
        ]);

        app(ConversationManager::class)->touch($conversationId);

        return $message;
    }

    public function storeAssistantMessage(string $conversationId, string $content, array $metadata = []): AiChatMessage
    {
        $message = AiChatMessage::create([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversationId,
            'agent' => $metadata['agent'] ?? config('ai-chat.default_agent', 'project_assistant'),
            'role' => 'assistant',
            'content' => $content,
            'meta' => $metadata,
        ]);

        app(ConversationManager::class)->touch($conversationId);

        return $message;
    }

    public function storeToolCallMessage(string $conversationId, array $toolCalls, array $toolResults): AiChatMessage
    {
        $message = AiChatMessage::create([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversationId,
            'agent' => config('ai-chat.default_agent', 'project_assistant'),
            'role' => 'assistant',
            'content' => '',
            'tool_calls' => $toolCalls,
            'tool_results' => $toolResults,
        ]);

        app(ConversationManager::class)->touch($conversationId);

        return $message;
    }

    public function getHistory(string $conversationId, int $limit = 100): Collection
    {
        return AiChatMessage::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    public function getLastMessage(string $conversationId): ?AiChatMessage
    {
        return AiChatMessage::where('conversation_id', $conversationId)
            ->orderByDesc('created_at')
            ->first();
    }
}
