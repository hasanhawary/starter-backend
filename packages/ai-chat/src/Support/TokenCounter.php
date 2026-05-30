<?php

namespace AiChat\Support;

class TokenCounter
{
    public static function estimate(string $text): int
    {
        return (int) ceil(str_word_count($text) * 1.3);
    }

    public static function estimateForMessages(array $messages): int
    {
        $total = 0;

        foreach ($messages as $message) {
            $content = is_array($message) ? ($message['content'] ?? '') : (string) $message;
            $total += static::estimate((string) $content);
        }

        return $total;
    }

    public static function withinBudget(int $used, int $budget): bool
    {
        return $used <= $budget;
    }
}
