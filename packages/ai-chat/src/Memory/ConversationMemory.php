<?php

namespace AiChat\Memory;

use AiChat\Models\AiChatMessage;
use AiChat\Models\AiMemory;

class ConversationMemory
{
    public function summarize(string $conversationId): string
    {
        $memories = AiMemory::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        if ($memories->isEmpty()) {
            return '';
        }

        $summary = $memories->map(fn (AiMemory $memory) => '- '.$memory->content)->implode("\n");

        $messageCount = AiChatMessage::where('conversation_id', $conversationId)->count();

        return "Conversation ({$messageCount} messages) key memories:\n{$summary}";
    }

    public function extractKeyFacts(string $conversationId): array
    {
        $memories = AiMemory::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $facts = [];

        foreach ($memories as $memory) {
            $metadata = $memory->metadata ?? [];
            $extractedFacts = $metadata['key_facts'] ?? [];

            if (is_array($extractedFacts)) {
                $facts = array_merge($facts, $extractedFacts);
            }

            if ($metadata['summary'] ?? null) {
                $facts[] = $metadata['summary'];
            }
        }

        return array_values(array_unique($facts));
    }
}
