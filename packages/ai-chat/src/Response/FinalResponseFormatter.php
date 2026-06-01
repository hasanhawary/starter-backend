<?php

namespace AiChat\Response;

use AiChat\Pipeline\ExecutionPlan;

class FinalResponseFormatter
{
    public function format(string $response, string $userMessage, ?ExecutionPlan $plan = null): string
    {
        $response = trim($response);
        $originalResponse = $response;

        if ($response === '') {
            return $response;
        }

        $response = $this->extractStructuredFinalContent($response);
        $response = $this->extractFinalTaggedContent($response);
        $response = $this->extractLabeledFinalAnswer($response);
        $response = $this->removeProtocolFragments($response);
        $response = $this->removeEchoedUserMessage($response, $userMessage);
        $response = $this->extractUserFacingAnswer($response, $userMessage);
        $response = $this->removeKnowledgeSourcePreamble($response);
        $response = $this->removeReasoningPreamble($response, $userMessage);
        $response = $this->removeProtocolFragments($response);
        $response = $this->removeBadPhrases($response);
        $response = $this->removeToolMentions($response);

        if ($plan !== null) {
            $response = $this->applyPlanRules($response, $plan, $userMessage);
        }

        $response = $this->normalizeWhitespace($response);

        return $response !== '' ? $response : $this->fallbackResponse($userMessage, $originalResponse);
    }

    public function sanitizeStreamDelta(string $chunk, string &$buffer, bool $flush = false): string
    {
        if ($chunk !== '') {
            $buffer .= $chunk;
        }

        if ($buffer === '') {
            return '';
        }

        $holdLength = $flush ? 0 : 80;
        $processLength = max(0, mb_strlen($buffer) - $holdLength);

        if ($processLength === 0) {
            return '';
        }

        $process = mb_substr($buffer, 0, $processLength);
        $buffer = mb_substr($buffer, $processLength);

        return $this->removeProtocolFragments($process, trim: false);
    }

    protected function extractFinalTaggedContent(string $response): string
    {
        if (preg_match_all('/<\s*final\s*>(.*?)<\s*\/\s*final\s*>/isu', $response, $matches) > 0) {
            return trim((string) collect($matches[1])->last());
        }

        if (preg_match('/<\s*final\s*>(.*)$/isu', $response, $match) === 1) {
            return trim($match[1]);
        }

        return $response;
    }

    protected function extractStructuredFinalContent(string $response): string
    {
        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            return $response;
        }

        foreach (['final', 'final_answer', 'answer', 'content', 'text', 'message'] as $key) {
            $value = data_get($decoded, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $response;
    }

    protected function extractLabeledFinalAnswer(string $response): string
    {
        if (preg_match_all('/(?:^|\n|\r|\.\s*)(?:final answer|final|answer|the answer is)\s*:\s*(.+)$/isu', $response, $matches) > 0) {
            return trim((string) collect($matches[1])->last());
        }

        return $response;
    }

    protected function removeProtocolFragments(string $response, bool $trim = true): string
    {
        $patterns = [
            '/<\s*\/?\s*final\s*>/iu',
            '/<\s*(analysis|thinking|reasoning|tool_call|tool|tool_result)[^>]*>.*?<\s*\/\s*\1\s*>/isu',
            '/<\s*\/?\s*(analysis|thinking|reasoning|tool_call|tool|tool_result)[^>]*>/iu',
            '/<\s*system-reminder\s*>.*?<\s*\/\s*system-reminder\s*>/isu',
            '/<\s*\/?\s*system-reminder\s*>/iu',
            '/\btags,?\s+and\s+the\s+text\s+must\s+be\s+exactly\s+what\s+the\s+user\s+should\s+see\.?\s*/iu',
            '/\bthe\s+text\s+must\s+be\s+exactly\s+what\s+the\s+user\s+should\s+see\.?\s*/iu',
            '/\bOutput\s+protocol:[^\S\r\n]*[^\n]*(?:\n|$)/iu',
            '/^[^\S\r\n]*(analysis|reasoning|thinking|chain of thought|internal reasoning|tool call|tool result)[^\S\r\n]*:?[^\S\r\n]*[^\n]*(?:\n|$)/ium',
            '/\bhidden\s+thoughts?\b\s*/iu',
            '/\binternal\s+instructions?\b\s*/iu',
            '/\bguideline\s+explanations?\b\s*/iu',
            '/\bdraft\s+text\b\s*/iu',
            '/\bprotocol\s+text\b\s*/iu',
            '/\bhidden\s+instruction\s+fragments?\b\s*/iu',
        ];

        $response = (string) preg_replace($patterns, '', $response);

        return $trim ? trim($response) : $response;
    }

    protected function removeEchoedUserMessage(string $response, string $userMessage): string
    {
        $userMessage = trim($userMessage);

        if ($userMessage === '' || ! str_starts_with($response, $userMessage)) {
            return $response;
        }

        return trim(mb_substr($response, mb_strlen($userMessage)));
    }

    protected function extractUserFacingAnswer(string $response, string $userMessage): string
    {
        if (! $this->isArabic($userMessage) || ! $this->hasInternalReasoning($response)) {
            return $response;
        }

        $arabicAnswer = $this->lastArabicAnswer($response);

        if ($arabicAnswer !== '') {
            return $arabicAnswer;
        }

        return $this->arabicNameAnswerFromReasoning($response) ?: $response;
    }

    protected function lastArabicAnswer(string $response): string
    {
        preg_match_all('/[\p{Arabic}][\p{Arabic}\s،؟!.]+/u', $response, $matches);

        return collect($matches[0] ?? [])
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => mb_strlen($part) > 8)
            ->last() ?? '';
    }

    protected function hasInternalReasoning(string $response): bool
    {
        return preg_match('/\b(let me|conversation history|from the conversation|i can see|based on|the user|user\'s name|appears to be|most recent exchange|i should|i need to|i will|guidelines?|system seems|assistant responded|user said)\b/i', $response) === 1;
    }

    protected function arabicNameAnswerFromReasoning(string $response): string
    {
        if (preg_match('/\b(?:user\'s name is|name appears to be|name is|appears to be)\s+([\p{Latin}]+)\b/iu', $response, $match) !== 1) {
            return '';
        }

        $name = match (strtolower($match[1])) {
            'hassan', 'hasan' => 'حسن',
            'muhammad', 'mohammed', 'mohamed', 'mohammad' => 'محمد',
            default => $match[1],
        };

        return 'نعم، اسمك '.$name.'.';
    }

    protected function removeKnowledgeSourcePreamble(string $response): string
    {
        if (preg_match('/\b(from the knowledge base|from source \[?\d+\]?|there are consistent rules across|entry \d+|i have enough information)\b/i', $response) !== 1) {
            return $response;
        }

        if (preg_match('/\bThe\s+[^.\n:]{3,80}\s+(?:is|are):\s*.+$/isu', $response, $match) === 1) {
            return trim($match[0]);
        }

        $response = preg_replace('/^\s*From the knowledge base:\s*/iu', '', $response);
        $response = preg_replace('/\bEntry\s+\d+:\s*/iu', '', (string) $response);
        $response = preg_replace('/\bI have enough information[^.؟!]*[.؟!]\s*/iu', '', (string) $response);
        $response = preg_replace('/\b\d+\.\s*From source \[?\d+\]?:\s*/iu', '', (string) $response);
        $response = preg_replace('/\bFrom source \[?\d+\]?:\s*/iu', '', (string) $response);
        $response = preg_replace('/"([^"\n]+)"(?:\s+and\s+)?/u', '$1. ', (string) $response);
        $response = preg_replace('/\s+There are consistent rules across[^:]*:\s*/iu', "\n", (string) $response);
        $response = preg_replace('/\s+-\s+/u', "\n- ", (string) $response);

        return trim((string) $response);
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
            '/^this looks like\b/i',
            '/^the user\b/i',
            '/^according to\b/i',
            '/^since the user\b/i',
            '/^since it\b/i',
            '/^this is a\b/i',
            '/^the guidelines?\b/i',
            '/^let me\b/i',
            '/^i should\b/i',
            '/^i need to\b/i',
            '/^i will\b/i',
            '/^i\'ll\b/i',
            '/^i am going to\b/i',
            '/^so i\b/i',
            '/^so the\b/i',
            '/^the function returned\b/i',
            '/^the tool returned\b/i',
            '/^in arabic\b/i',
            '/^given the context\b/i',
            '/^given that\b/i',
            '/^looking at\b/i',
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
        if (preg_match('/^looking at\b/i', $segment) === 1) {
            return '';
        }

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

        if (preg_match('/(?:^|[\s.])(`?Hello!|Hi!|Hey!|You\'re welcome[.!]?|I am your AI assistant\.?)/u', $segment, $match) === 1) {
            return trim($match[1]);
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

    protected function fallbackResponse(string $userMessage, string $originalResponse): string
    {
        if (trim($originalResponse) === '') {
            return '';
        }

        return $this->isArabic($userMessage)
            ? 'عذراً، لم أتمكن من تجهيز رد مناسب. حاول مرة أخرى.'
            : 'Sorry, I could not prepare a clean answer. Please try again.';
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
