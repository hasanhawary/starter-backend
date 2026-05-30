<?php

namespace AiChat\RAG;

use AiChat\Support\TokenCounter;

class ContextBuilder
{
    public function build(array $retrievedChunks, int $maxTokens = 2000): string
    {
        if (empty($retrievedChunks)) {
            return '';
        }

        $context = '';
        $usedTokens = 0;
        $headerTokens = TokenCounter::estimate('## Knowledge Context');
        $usedTokens += $headerTokens;
        $context .= "## Knowledge Context\n\n";

        foreach ($retrievedChunks as $index => $chunk) {
            $content = $chunk['content'] ?? '';
            $source = $chunk['source'] ?? 'unknown';
            $score = $chunk['score'] ?? 0;

            $entry = $this->formatEntry($content, $source, $score, $index + 1);
            $entryTokens = TokenCounter::estimate($entry);

            if ($usedTokens + $entryTokens > $maxTokens) {
                $remaining = $maxTokens - $usedTokens;

                if ($remaining > 50) {
                    $context .= $this->truncateEntry($content, $source, $score, $index + 1, $remaining);
                }

                break;
            }

            $context .= $entry;
            $usedTokens += $entryTokens;
        }

        $context .= "\n---\nUse the above knowledge to inform your response. Cite sources when relevant.\n";

        return $context;
    }

    protected function formatEntry(string $content, string $source, float $score, int $number): string
    {
        $attribution = "[Source: {$source}, Relevance: ".round($score * 100, 1).'%]';

        return "### Context {$number} {$attribution}\n{$content}\n\n";
    }

    protected function truncateEntry(string $content, string $source, float $score, int $number, int $tokenBudget): string
    {
        $header = "### Context {$number} [Source: {$source}]\n";
        $headerTokens = TokenCounter::estimate($header);
        $remainingBudget = $tokenBudget - $headerTokens;

        if ($remainingBudget <= 0) {
            return '';
        }

        $words = explode(' ', $content);
        $truncated = '';

        foreach ($words as $word) {
            $candidate = $truncated !== '' ? "{$truncated} {$word}" : $word;

            if (TokenCounter::estimate($candidate) > $remainingBudget) {
                break;
            }

            $truncated = $candidate;
        }

        return $header.$truncated."...\n\n";
    }
}
