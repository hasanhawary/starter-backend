<?php

namespace AiChat\Chat;

use AiChat\Models\AiChatConversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ConversationManager
{
    public function create(?string $userId = null, ?string $title = null, array $metadata = []): AiChatConversation
    {
        return AiChatConversation::create([
            'id' => (string) Str::uuid7(),
            'session_id' => $userId,
            'title' => $title ?? 'New Chat',
            'metadata' => $metadata,
            'last_message_at' => now(),
        ]);
    }

    public function get(string $id): ?AiChatConversation
    {
        return AiChatConversation::find($id);
    }

    public function listForUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return AiChatConversation::where('session_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }

    public function delete(string $id): bool
    {
        $conversation = $this->get($id);

        if (! $conversation) {
            return false;
        }

        $conversation->messages()->delete();
        $conversation->delete();

        return true;
    }

    public function updateTitle(string $id, string $title): AiChatConversation
    {
        $conversation = $this->get($id);

        $conversation->update(['title' => $title]);

        return $conversation->fresh();
    }

    public function touch(string $id): void
    {
        AiChatConversation::where('id', $id)->update([
            'updated_at' => now(),
            'last_message_at' => now(),
        ]);
    }
}
