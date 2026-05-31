<?php

namespace AiChat\Response;

use AiChat\Pipeline\ExecutionPlan;

class FinalResponseFormatter
{
    public function format(string $response, string $userMessage, ?ExecutionPlan $plan = null): string
    {
        $response = trim($response);

        if ($response === '') {
            return $response;
        }

        $response = $this->extractFinalTaggedContent($response);
        $response = $this->removeReasoningPreamble($response, $userMessage);
        $response = $this->removeBadPhrases($response);
        $response = $this->removeToolMentions($response);

        if ($plan !== null) {
            $response = $this->applyPlanRules($response, $plan, $userMessage);
        }

        return $this->normalizeWhitespace($response);
    }

    protected function extractFinalTaggedContent(string $response): string
    {
        if (preg_match('/<final>(.*?)<\/final>/isu', $response, $match) === 1) {
            return trim($match[1]);
        }

        if (preg_match('/<final>(.*)$/isu', $response, $match) === 1) {
            return trim($match[1]);
        }

        return $response;
    }

    protected function removeReasoningPreamble(string $response, string $userMessage): string
    {
        $segments = preg_split('/\n+|(?<=[.!؟?])\s+/u', $response, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($segments)) {
            return $response;
        }

        $kept = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            if ($this->isReasoningSegment($segment)) {
                $tail = $this->extractFinalAnswerTail($segment, $userMessage);

                if ($tail !== '') {
                    $kept[] = $tail;
                }

                continue;
            }

            $kept[] = $segment;
        }

        return trim(implode(' ', $kept));
    }

    protected function isReasoningSegment(string $segment): bool
    {
        $patterns = [
            '/^the user\b/i',
            '/^according to\b/i',
            '/^since the user\b/i',
            '/^this is a\b/i',
            '/^the guidelines?\b/i',
            '/^i should\b/i',
            '/^i need to\b/i',
            '/^i will\b/i',
            '/^i\'ll\b/i',
            '/^i am going to\b/i',
            '/^so i\b/i',
            '/^the function returned\b/i',
            '/^the tool returned\b/i',
            '/^in arabic\b/i',
            '/^given the context\b/i',
            '/^it can be\b/i',
            '/^it might be\b/i',
            '/is an arabic greeting/i',
            '/similar to\s+["\']?(how are you|what\'s up)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $segment) === 1) {
                return true;
            }
        }

        return false;
    }

    protected function extractFinalAnswerTail(string $segment, string $userMessage): string
    {
        if ($this->isArabic($userMessage)) {
            preg_match_all('/[\p{Arabic}][\p{Arabic}\s،؟!.]+/u', $segment, $matches);
            $arabicParts = array_values(array_filter(
                array_map('trim', $matches[0] ?? []),
                fn (string $part): bool => mb_strlen($part) > 8,
            ));

            if (! empty($arabicParts)) {
                return end($arabicParts) ?: '';
            }
        }

        if (preg_match('/(?:^|[\s.])(`?Hello!|Hi!|Hey!|You\'re welcome[.!]?|I am your AI assistant\.?)(.*)$/u', $segment, $match) === 1) {
            return trim($match[1].($match[2] ?? ''));
        }

        return '';
    }

    protected function applyPlanRules(string $response, ExecutionPlan $plan, string $userMessage): string
    {
        $intent = $this->getIntent($plan);

        if ($this->isDirectIntent($intent)) {
            $response = $this->enforceBrief($response, 3);
        }

        if ($this->hasTools($plan)) {
            $response = $this->removeToolMentions($response);
        }

        if ($this->isUnknownIntent($intent) || $this->needsClarification($plan)) {
            $response = $this->ensureClarification($response, $userMessage);
        }

        return $response;
    }

    protected function getIntent(?ExecutionPlan $plan): ?string
    {
        if ($plan === null) {
            return null;
        }

        return $plan->intent ?? null;
    }

    protected function hasTools(?ExecutionPlan $plan): bool
    {
        if ($plan === null) {
            return false;
        }

        return ! empty($plan->tools ?? []);
    }

    protected function needsClarification(?ExecutionPlan $plan): bool
    {
        if ($plan === null) {
            return false;
        }

        return (bool) ($plan->needsClarification ?? false);
    }

    protected function isDirectIntent(?string $intent): bool
    {
        return in_array($intent, [
            'direct',
            'greeting',
            'small_talk',
            'identity',
        ], true);
    }

    protected function isUnknownIntent(?string $intent): bool
    {
        return in_array($intent, [
            'unknown',
            'ambiguous',
            'clarification',
        ], true);
    }

    protected function removeBadPhrases(string $response): string
    {
        $patterns = [
            '/\b(as an ai language model|as an ai assistant|as an ai|as a language model)\b[:,]?\s*/i',
            '/\b(i am an ai language model|i am an ai assistant)\b[:,]?\s*/i',
            '/\b(you can run|you could run|you can execute)\b[^.?!؟]*(select count|select \*|sql query)[^.?!؟]*[.?!؟]?/i',
        ];

        return trim((string) preg_replace($patterns, '', $response));
    }

    protected function removeToolMentions(string $response): string
    {
        $patterns = [
            '/\busing the\s+[\w.\-:]+\s+(tool|function),?\s*/i',
            '/\bi used the\s+[\w.\-:]+\s+(tool|function),?\s*/i',
            '/\bi called the\s+[\w.\-:]+\s+(tool|function),?\s*/i',
            '/\bafter running\s+[\w.\-:]+,?\s*/i',
            '/\bbased on the\s+[\w.\-:]+\s+(tool|function),?\s*/i',
            '/\baccording to the\s+[\w.\-:]+\s+(tool|function),?\s*/i',
            '/\bthe result from\s+[\w.\-:]+\s+(shows?|returns?|gives?|indicates?)\s*/i',
            '/باستخدام\s+(أداة|اداة|الدالة|دالة)\s+[\w.\-:]+،?\s*/u',
            '/بعد\s+تشغيل\s+[\w.\-:]+،?\s*/u',
            '/حسب\s+(أداة|اداة|الدالة|دالة)\s+[\w.\-:]+،?\s*/u',
            '/بناءً?\s+على\s+(أداة|اداة|الدالة|دالة)\s+[\w.\-:]+،?\s*/u',
        ];

        return trim((string) preg_replace($patterns, '', $response));
    }

    protected function enforceBrief(string $response, int $maxSentences): string
    {
        $sentences = preg_split('/(?<=[.!؟?])\s+/u', $response, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($sentences)) {
            return $response;
        }

        if (count($sentences) > $maxSentences) {
            return implode(' ', array_slice($sentences, 0, $maxSentences));
        }

        return $response;
    }

    protected function ensureClarification(string $response, string $userMessage): string
    {
        if (str_contains($response, '?') || str_contains($response, '؟')) {
            return $response;
        }

        $clarification = $this->isArabic($userMessage)
            ? 'ممكن توضح تقصد إيه؟'
            : 'Could you clarify what you need?';

        if ($response === '') {
            return $clarification;
        }

        return trim($response.' '.$clarification);
    }

    protected function isArabic(string $text): bool
    {
        return preg_match('/\p{Arabic}/u', $text) === 1;
    }

    protected function normalizeWhitespace(string $response): string
    {
        $response = preg_replace('/[ \t]+/u', ' ', $response);
        $response = preg_replace('/\n{3,}/u', "\n\n", (string) $response);

        return trim((string) $response);
    }
}
