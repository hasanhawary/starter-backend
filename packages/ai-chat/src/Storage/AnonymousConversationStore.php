<?php

namespace AiChat\Storage;

use AiChat\Models\AiChatConversation;
use AiChat\Models\AiChatMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;

class AnonymousConversationStore implements ConversationStore
{
    public function latestConversationId(string|int $userId): ?string
    {
        return AiChatConversation::where('session_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->first()?->id;
    }

    public function storeConversation(string|int|null $userId, string $title): string
    {
        return AiChatConversation::create([
            'id' => (string) Str::uuid7(),
            'session_id' => $userId,
            'title' => $title,
        ])->id;
    }

    public function storeUserMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt): string
    {
        $message = AiChatMessage::create([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversationId,
            'session_id' => $userId,
            'agent' => $prompt->agent::class,
            'role' => 'user',
            'content' => $prompt->prompt,
            'attachments' => $prompt->attachments->toArray(),
        ]);

        $this->touchConversation($conversationId);

        return $message->id;
    }

    public function storeAssistantMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt, AgentResponse $response): string
    {
        $message = AiChatMessage::create([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversationId,
            'session_id' => $userId,
            'agent' => $prompt->agent::class,
            'role' => 'assistant',
            'content' => $response->text,
            'tool_calls' => $response->toolCalls->values(),
            'tool_results' => $response->toolResults->values(),
            'usage' => $response->usage,
            'meta' => $response->meta,
        ]);

        $this->touchConversation($conversationId);

        return $message->id;
    }

    public function getRecentConversations(string $sessionId, int $limit = 3): Collection
    {
        return AiChatConversation::where('session_id', $sessionId)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->pluck('id');
    }

    public function getLatestConversationMessages(string $conversationId, int $limit): Collection
    {
        return AiChatMessage::where('conversation_id', $conversationId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->flatMap(fn (AiChatMessage $record) => $this->transformMessage($record));
    }

    protected function touchConversation(string $conversationId): void
    {
        AiChatConversation::where('id', $conversationId)
            ->update(['updated_at' => now()]);
    }

    protected function transformMessage(AiChatMessage $record): array
    {
        $toolCalls = collect($record->tool_calls)->values();
        $toolResults = collect($record->tool_results)->values();

        if ($record->role === 'user') {
            $attachments = $this->filterAttachments($record->attachments);

            if ($attachments->isNotEmpty()) {
                return [new UserMessage($record->content, $attachments)];
            }

            return [new Message('user', $record->content)];
        }

        if ($toolCalls->isNotEmpty()) {
            $messages = [];

            $messages[] = new AssistantMessage(
                $record->content ?: '',
                $toolCalls->map(fn ($tc) => new ToolCall(
                    id: $tc['id'],
                    name: $tc['name'],
                    arguments: $tc['arguments'],
                    resultId: $tc['result_id'] ?? null,
                    reasoningId: $tc['reasoning_id'] ?? null,
                    reasoningSummary: $tc['reasoning_summary'] ?? null,
                )),
            );

            if ($toolResults->isNotEmpty()) {
                $messages[] = new ToolResultMessage(
                    $toolResults->map(fn ($tr) => new ToolResult(
                        id: $tr['id'],
                        name: $tr['name'],
                        arguments: $tr['arguments'],
                        result: $tr['result'],
                        resultId: $tr['result_id'] ?? null,
                    )),
                );
            }

            return $messages;
        }

        return [new AssistantMessage($record->content)];
    }

    protected function filterAttachments(?array $attachments): Collection
    {
        if (empty($attachments) || ! array_is_list($attachments)) {
            return collect();
        }

        return collect($attachments)
            ->filter(fn ($a) => is_array($a))
            ->values();
    }
}
