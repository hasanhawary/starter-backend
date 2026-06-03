<?php

namespace AiChat\Prompt;

use AiChat\Pipeline\ChatPayload;

/**
 * Builds the final system prompt from a ChatPayload.
 *
 * Single source of truth used by both SendToProvider (pipeline path)
 * and AiChatController (streaming path).
 */
class SystemPromptBuilder
{
    public function build(ChatPayload $payload): string
    {
        $parts = [];

        $agentPrompt = $payload->agent?->systemPrompt()
            ?? config('ai-chat.conversations.default_system_prompt', '');

        if ($agentPrompt !== '') {
            $parts[] = $agentPrompt;
        }

        if (! empty($payload->context)) {
            $parts[] = "## Context\n".json_encode($payload->context, JSON_PRETTY_PRINT);
        }

        if (! empty($payload->knowledge)) {
            $parts[] = "## Knowledge Base\n".collect($payload->knowledge)
                ->map(fn ($k, $i) => '['.($i + 1).'] '.(is_array($k) ? json_encode($k) : (string) $k))
                ->implode("\n");
        }

        if (! empty($payload->memory)) {
            $parts[] = "## Conversation Memory\n".collect($payload->memory)
                ->map(fn ($m) => is_array($m) ? json_encode($m) : (string) $m)
                ->implode("\n");
        }

        return implode("\n\n", $parts);
    }
}
