<?php

namespace AiChat\Chat;

use AiChat\Pipeline\ExecutionPlan;
use AiChat\Planning\HeuristicPlanner;
use AiChat\Support\ArabicTextNormalizer;

class HistorySelector
{
    protected array $recallPatterns = [
        'what is my name',
        'do you remember my name',
        'do you remember what i told you',
        'what did i say',
        'what did we talk about',
        'what was i asking',
        'what was the answer',
        'i told you',
        'i said',
        'i asked',
        'we discussed',
        'from earlier',
        'from before',
        'in our conversation',
        'in the conversation',
        'remember my',
        'remember what',
        'recall',
        'اسمي ايه',
        'اسمي اي',
        'اسمي مين',
        'مين اسمي',
        'ايه اسمي',
        'اي اسمي',
        'ايش اسمي',
        'فاكر اسمي',
        'فاكر اسم',
        'فاكرني',
        'تفتكر اسمي',
        'تفتكرني',
        'تفتكر',
        'تذكر',
        'هل فاكر',
        'مين انا',
        'قولتلي ايه',
        'قلت ايه',
        'سألت عن ايه',
        'قلتلك ايه',
        'محتاج اتعلمت',
        'محتاج ذكرت',
        'لسه قايلك',
        'قلتلك قبل كده',
    ];

    protected array $followUpPatterns = [
        'continue',
        'كمل',
        'go on',
        'continue please',
        'carry on',
        'وضح اكتر',
        'ليه كده',
        'ليه',
        'why',
        'explain more',
        'اشرح اكتر',
        'ازاي',
        'how',
        'tell me more',
        'اكمل',
        'واستمر',
        'more details',
        'تفاصيل اكتر',
        'again',
        'مرة تانية',
        'repeat',
        'كرر',
    ];

    protected array $summaryPatterns = [
        'summarize our conversation',
        'summarize the conversation',
        'لخص المحادثة',
        'لخص المحادثه',
        'لخص اللي حصل',
        'لخص اللي اتكلمنا فيه',
        'what did we discuss',
        'what did we talk about',
        'what have we covered',
        'recap',
        'ايه اللي اتكلمنا فيه',
        'ايه اللي قلناه',
        'ملخص المحادثة',
        'خلاصة المحادثه',
        'summary of conversation',
        'conversation summary',
    ];

    protected array $identityPatterns = [
        'my name is',
        'i am called',
        'call me',
        'whats your name',
        'what is your name',
        'مين انت',
        'انت مين',
        'اسمك ايه',
        'اسمك اي',
        'ما اسمك',
    ];

    protected array $interrogativeWords = [
        'ايه', 'اي', 'مين', 'اين', 'فين', 'امتى', 'ليه',
        'ازاي', 'ايش', 'هل', 'كيف', 'ما', 'ماذا', 'من',
        'what', 'who', 'where', 'when', 'why', 'how', 'is',
        'do you', 'are you', 'can you',
    ];

    public function __construct(
        protected HeuristicPlanner $planner,
    ) {}

    public function select(ExecutionPlan $plan, string $currentMessage): HistoryPolicy
    {
        $normalized = ArabicTextNormalizer::normalize($currentMessage);

        if ($this->isSummaryRequest($normalized)) {
            return HistoryPolicy::summary();
        }

        if ($this->isRecallQuestion($normalized)) {
            $query = $this->extractRecallQuery($normalized, $currentMessage);

            return HistoryPolicy::relevant($query, min($plan->historyLimit, 8));
        }

        if ($plan->isMemoryRequest()) {
            return HistoryPolicy::relevant($plan->memoryQuery ?? $currentMessage, $plan->memoryLimit);
        }

        if ($this->isFollowUp($normalized)) {
            return HistoryPolicy::recent(min($plan->historyLimit, 6));
        }

        if ($this->isIdentityStatement($normalized)) {
            return HistoryPolicy::none();
        }

        if ($plan->intent === 'direct' && $plan->historyLimit === 0) {
            return HistoryPolicy::none();
        }

        if ($plan->intent === 'direct') {
            return HistoryPolicy::recent(2);
        }

        return HistoryPolicy::recent($plan->historyLimit);
    }

    public function filterMessages(iterable $messages, HistoryPolicy $policy, string $currentMessage): iterable
    {
        if (! $policy->useHistory) {
            return [];
        }

        $allMessages = is_array($messages) ? $messages : iterator_to_array($messages);

        if (empty($allMessages)) {
            return [];
        }

        return match ($policy->mode) {
            'recent' => $this->takeRecent($allMessages, $policy->limit),
            'relevant' => $this->filterRelevant($allMessages, $policy->query, $policy->limit),
            'summary' => $allMessages,
            default => [],
        };
    }

    public function buildHistoryLabel(HistoryPolicy $policy): string
    {
        if (! $policy->useHistory) {
            return '';
        }

        return match ($policy->mode) {
            'recent' => '',
            'relevant' => 'The following is relevant previous context from this conversation. These messages have already been answered. Use them only if the user\'s latest message explicitly requires this context. Do not re-answer any previous questions.',
            'summary' => 'The following is the full conversation history. The user is asking for a summary. Use all messages to provide the requested summary. Do not re-answer any previous individual questions.',
            default => '',
        };
    }

    protected function isRecallQuestion(string $normalized): bool
    {
        foreach ($this->recallPatterns as $pattern) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);

            if ($normPattern !== '' && str_contains($normalized, $normPattern)) {
                return true;
            }
        }

        return false;
    }

    protected function isFollowUp(string $normalized): bool
    {
        foreach ($this->followUpPatterns as $pattern) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);

            if ($normPattern !== '' && $normalized === $normPattern) {
                return true;
            }
        }

        $shortFollowUps = ['كمل', 'continue', 'وضح', 'اكتر', 'go on', 'more'];

        foreach ($shortFollowUps as $pattern) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);

            if ($normPattern !== '' && str_starts_with($normalized, $normPattern)) {
                return true;
            }
        }

        return false;
    }

    protected function isSummaryRequest(string $normalized): bool
    {
        foreach ($this->summaryPatterns as $pattern) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);

            if ($normPattern !== '' && str_contains($normalized, $normPattern)) {
                return true;
            }
        }

        return false;
    }

    protected function isIdentityStatement(string $normalized): bool
    {
        foreach ($this->identityPatterns as $pattern) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);

            if ($normPattern !== '' && str_contains($normalized, $normPattern)) {
                return true;
            }
        }

        if ($this->isNameDeclaration($normalized)) {
            return true;
        }

        return false;
    }

    protected function isNameDeclaration(string $normalized): bool
    {
        if (! str_contains($normalized, 'اسمي')) {
            return false;
        }

        if ($this->isQuestion($normalized)) {
            return false;
        }

        $afterName = trim(strstr($normalized, 'اسمي'));
        $afterName = trim(substr($afterName, strlen('اسمي')));

        if ($afterName === '') {
            return false;
        }

        $questionWords = ['ايه', 'اي', 'مين', 'اين', 'ليه', 'ازاي', 'ايش', 'ما'];
        foreach ($questionWords as $qw) {
            if (str_starts_with($afterName, $qw)) {
                return false;
            }
        }

        return true;
    }

    protected function isQuestion(string $normalized): bool
    {
        if (str_contains($normalized, '?') || str_contains($normalized, '؟')) {
            return true;
        }

        foreach ($this->interrogativeWords as $word) {
            $normWord = ArabicTextNormalizer::normalize($word);
            if ($normWord !== '' && str_contains($normalized, $normWord)) {
                return true;
            }
        }

        return false;
    }

    protected function extractRecallQuery(string $normalized, string $original): string
    {
        $extractors = [
            'اسمي' => 'اسمي',
            'اسم' => 'اسم',
            'name' => 'user name',
            'تفتكر' => null,
            'قلتلك' => null,
            'قوله' => null,
            'told you' => null,
            'said' => null,
            'asked' => null,
        ];

        foreach ($extractors as $keyword => $query) {
            if (str_contains($normalized, $keyword)) {
                return $query ?? $original;
            }
        }

        return $original;
    }

    protected function takeRecent(array $messages, int $limit): array
    {
        return array_slice($messages, -$limit);
    }

    protected function filterRelevant(array $messages, ?string $query, int $limit): array
    {
        if (! $query) {
            return $this->takeRecent($messages, $limit);
        }

        $normalizedQuery = ArabicTextNormalizer::normalize($query);
        $queryWords = explode(' ', $normalizedQuery);
        $queryWords = array_filter($queryWords, fn (string $w) => mb_strlen($w) > 2);

        if (empty($queryWords)) {
            return $this->takeRecent($messages, $limit);
        }

        $scored = [];

        foreach ($messages as $message) {
            $content = $this->extractMessageContent($message);
            $normalizedContent = ArabicTextNormalizer::normalize($content);

            $score = 0;

            foreach ($queryWords as $word) {
                if (str_contains($normalizedContent, $word)) {
                    $score++;
                }
            }

            if ($score > 0) {
                $scored[] = ['message' => $message, 'score' => $score];
            }
        }

        if (empty($scored)) {
            return $this->takeRecent($messages, $limit);
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'message'), 0, $limit);
    }

    protected function extractMessageContent(mixed $message): string
    {
        if (is_array($message)) {
            return (string) ($message['content'] ?? '');
        }

        if (is_object($message) && method_exists($message, 'content')) {
            return (string) $message->content();
        }

        if (is_object($message) && property_exists($message, 'content')) {
            return (string) $message->content;
        }

        return (string) $message;
    }
}
