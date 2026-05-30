<?php

namespace AiChat\Services;

use AiChat\Http\Resources\ConversationResource;
use AiChat\Models\AiChatConversation;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function listConversations(array $validated): mixed
    {
        $query = AiChatConversation::where('session_id', $validated['session_id'])
            ->orderBy('updated_at', 'desc');

        return wrapPaginate($query, ConversationResource::class);
    }

    public function getConversation(string $id, string $sessionId): ?AiChatConversation
    {
        return AiChatConversation::where('id', $id)
            ->where('session_id', $sessionId)
            ->first();
    }

    public function deleteConversation(string $id, string $sessionId): bool
    {
        return DB::transaction(function () use ($id, $sessionId) {
            $conversation = $this->getConversation($id, $sessionId);

            if (! $conversation) {
                return false;
            }

            $conversation->messages()->delete();
            $conversation->delete();

            return true;
        });
    }
}
