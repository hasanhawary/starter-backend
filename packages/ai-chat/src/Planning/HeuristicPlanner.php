<?php

namespace AiChat\Planning;

use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Planning\ToolSearch\ArrayToolSearchIndex;
use AiChat\Planning\ToolSearch\ToolSearchIndex;
use AiChat\Support\ArabicTextNormalizer;

class HeuristicPlanner
{
    protected array $liveDataPatterns = [
        'how many' => 'count',
        'count of' => 'count',
        'number of' => 'count',
        'total' => 'count',
        'كم' => 'count',
        'عدد' => 'count',
        'كام' => 'count',
        'اجمالي' => 'count',
        'مجموع' => 'count',
        'list' => 'list',
        'show me' => 'list',
        'get all' => 'list',
        'latest' => 'latest',
        'recent' => 'latest',
        'newest' => 'latest',
        'last' => 'latest',
        'اخر' => 'latest',
        'احدث' => 'latest',
        'جديد' => 'latest',
        'top' => 'analytics',
        'highest' => 'analytics',
        'lowest' => 'analytics',
        'most' => 'analytics',
        'اكتر' => 'analytics',
        'اكثر' => 'analytics',
        'compare' => 'analytics',
        'vs' => 'analytics',
        'versus' => 'analytics',
        'summary' => 'summary',
        'summarize' => 'summary',
        'ملخص' => 'summary',
        'لخص' => 'summary',
        'تقرير' => 'summary',
        'stats' => 'analytics',
        'statistics' => 'analytics',
        'analytics' => 'analytics',
        'احصائيات' => 'analytics',
        'قارن' => 'analytics',
        'مقارنه' => 'analytics',
    ];

    protected array $knowledgePatterns = [
        'what are the' => 'knowledge',
        'what is the' => 'knowledge',
        'explain' => 'knowledge',
        'اشرح' => 'knowledge',
        'اشرحلي' => 'knowledge',
        'ايه شروط' => 'knowledge',
        'ما شروط' => 'knowledge',
        'ايه قواعد' => 'knowledge',
        'سياسه' => 'knowledge',
        'سياسة' => 'knowledge',
        'policy' => 'knowledge',
        'rules' => 'knowledge',
        'how does' => 'knowledge',
        'how do' => 'knowledge',
        'ازاي' => 'knowledge',
        'docs' => 'knowledge',
        'documentation' => 'knowledge',
        'readme' => 'knowledge',
    ];

    protected array $projectStructurePatterns = [
        'where is' => 'project_structure',
        'فين' => 'project_structure',
        'مكان' => 'project_structure',
        'اي ملف' => 'project_structure',
        'انهي ملف' => 'project_structure',
        'which file' => 'project_structure',
        'which service' => 'project_structure',
        'which controller' => 'project_structure',
        'what module' => 'project_structure',
        'architecture' => 'project_structure',
        'structure' => 'project_structure',
    ];

    protected array $memoryPatterns = [
        'continue what we discussed' => 'memory',
        'as we discussed' => 'memory',
        'we talked about' => 'memory',
        'like before' => 'memory',
        'like we discussed' => 'memory',
        'from before' => 'memory',
        'previous conversation' => 'memory',
        'do you remember' => 'memory',
        'remember' => 'memory',
        'recall' => 'memory',
        'المرة اللي فاتت' => 'memory',
        'قبل كده' => 'memory',
        'كمل اللي فات' => 'memory',
        'كمل اللي قلته' => 'memory',
        'كمل التقرير اللي قولتلك عليه' => 'memory',
        'اللي قولتلك' => 'memory',
        'كمل اللي' => 'memory',
        'قلتلك' => 'memory',
        'زي ما قلتلك' => 'memory',
        'زي ما قولتلك' => 'memory',
        'فاكر التقرير' => 'memory',
        'فاكر اللي قولته' => 'memory',
        'فاكر' => 'memory',
        'تفتكر' => 'memory',
        'تذكر' => 'memory',
        'هل فاكر' => 'memory',
        'هل تفتكر' => 'memory',
        'هل تذكر' => 'memory',
        'فاكرني' => 'memory',
        'تفتكرني' => 'memory',
    ];

    protected array $greetingPatterns = [
        'ازيك', 'ازايك',
        'صباح الخير', 'مساء الخير',
        'اهلا', 'مرحبا',
        'سلام', 'السلام عليكم',
        'هلا', 'يا هلا', 'هلا والله',
        'يا هلا وغلا',
        'مرحبا يا مرحبا',
        'حي الله', 'الله يحييك',
        'hello', 'hi', 'hey', 'howdy',
        'good morning', 'good evening',
        'whats up', 'sup',
        'عامل ايه', 'عاملين ايه',
        'كيف حالك', 'كيفك', 'كيف الحال',
        'وش اخبارك', 'شخبارك', 'وش علومك',
        'ايش اخبارك', 'علومك',
        'عساك طيب',
        'تمام', 'الحمد لله',
        'اخبارك ايه', 'ايه الاخبار',
        'الدنيا ايه',
    ];

    protected array $complexAnalyticsIndicators = [
        'compare', 'vs ', 'versus', 'difference between',
        'relationship', 'correlation', 'trend', 'over time',
        'month over month', 'year over year',
        'قارن', 'مقارنه', 'الفرق بين',
        'اتجاه', 'ترند', 'نمو', 'انخفاض', 'ارتفاع',
        'شهر بشهر', 'سنه بسنه',
    ];

    protected array $businessRuleIndicators = [
        'rules', 'policy', 'calculated', 'calculation',
        'بيتحسب', 'قواعد', 'سياسه', 'شروط',
        'من paid', 'من المدفوع',
    ];

    protected ?ToolSearchIndex $searchIndex = null;

    protected array $greetingCache = [];

    protected array $dialectCache = [];

    public function __construct(
        protected ToolRegistry $toolRegistry,
    ) {}

    public function plan(string $message, array $availableTools = [], ?string $locale = null): ExecutionPlan
    {
        $normalizedMessage = ArabicTextNormalizer::normalize($message);
        $originalMessage = $message;
        $plan = new ExecutionPlan;
        $plan->planner = 'heuristic';

        $greetingMatch = $this->detectGreeting($normalizedMessage, $message);
        if ($greetingMatch !== null) {
            $plan->intent = 'direct';
            $plan->historyLimit = 0;
            $plan->historyMode = 'none';
            $plan->useRag = false;
            $plan->useMemory = false;
            $plan->tools = [];
            $plan->metadata['confidence'] = $greetingMatch['confidence'];
            $plan->metadata['reason'] = $greetingMatch['reason'];

            return $plan;
        }

        $detectedIntent = $this->detectIntent($normalizedMessage);
        $matchedTools = $this->matchTools($normalizedMessage, $availableTools);
        $bestToolScore = $this->bestToolScore($matchedTools, $normalizedMessage);

        $this->checkMemory($plan, $normalizedMessage, $originalMessage, $matchedTools);
        if ($plan->intent === 'memory') {
            $plan->historyMode = 'relevant';
            $plan->metadata['confidence'] = 0.90;
            $plan->metadata['reason'] = 'memory_phrase_match';

            return $plan;
        }

        $this->checkKnowledge($plan, $normalizedMessage, $originalMessage, $detectedIntent, $matchedTools);
        if ($plan->intent === 'knowledge' || $plan->intent === 'project_structure') {
            $plan->metadata['confidence'] = 0.80;
            $plan->metadata['reason'] = $plan->intent === 'project_structure' ? 'project_structure_match' : 'knowledge_pattern_match';

            return $plan;
        }

        if ($this->isComplexAnalytics($normalizedMessage)) {
            $hasBusinessRule = $this->hasBusinessRuleIndicator($normalizedMessage);
            $plan->intent = 'mixed';
            $plan->tools = $matchedTools;
            $plan->useRag = $hasBusinessRule;
            if ($hasBusinessRule) {
                $plan->ragQuery = $originalMessage;
            }
            $plan->metadata['confidence'] = 0.80;
            $plan->metadata['reason'] = 'complex_analytics'.($hasBusinessRule ? '_with_business_rule' : '');

            return $plan;
        }

        if (! empty($matchedTools) && $bestToolScore >= $this->toolMatchThreshold()) {
            $plan->tools = $matchedTools;
            $plan->intent = in_array($detectedIntent, ['analytics', 'mixed']) ? $detectedIntent : 'live_data';
            $plan->metadata['confidence'] = max(0.80, $bestToolScore);
            $plan->metadata['reason'] = 'tool_match';

            return $plan;
        }

        if (in_array($detectedIntent, ['live_data', 'analytics'])) {
            $plan->intent = $detectedIntent;
            $plan->metadata['confidence'] = 0.80;
            $plan->metadata['reason'] = 'live_data_pattern_match';

            return $plan;
        }

        $plan->intent = 'direct';
        $plan->historyMode = 'recent';
        $plan->metadata['confidence'] = 0.50;
        $plan->metadata['reason'] = 'unknown_direct';

        return $plan;
    }

    public function canHandle(string $message): bool
    {
        $normalized = ArabicTextNormalizer::normalize($message);

        if ($this->detectGreeting($normalized, $message) !== null) {
            return true;
        }

        foreach ($this->getMergedLiveDataPatterns() as $pattern => $type) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalized, $normPattern)) {
                return true;
            }
        }

        foreach ($this->getMergedKnowledgePatterns() as $pattern => $type) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalized, $normPattern)) {
                return true;
            }
        }

        foreach ($this->projectStructurePatterns as $pattern => $type) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalized, $normPattern)) {
                return true;
            }
        }

        foreach ($this->memoryPatterns as $pattern => $type) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalized, $normPattern)) {
                return true;
            }
        }

        foreach ($this->complexAnalyticsIndicators as $indicator) {
            $normIndicator = ArabicTextNormalizer::normalize($indicator);
            if (str_contains($normalized, $normIndicator)) {
                return true;
            }
        }

        $searchResults = $this->getSearchIndex()->search($message, 1);

        if (! empty($searchResults) && $searchResults[0]['score'] >= $this->toolMatchThreshold()) {
            return true;
        }

        return false;
    }

    public function getConfidence(ExecutionPlan $plan): float
    {
        return $plan->metadata['confidence'] ?? 0.50;
    }

    public function shouldUseLlm(ExecutionPlan $plan): bool
    {
        $confidence = $this->getConfidence($plan);
        $threshold = (float) config('ai-chat.planning.heuristic_confidence_threshold', 0.75);

        if ($confidence >= $threshold) {
            return false;
        }

        if (in_array($plan->intent, ['mixed', 'clarification'])) {
            return true;
        }

        return $confidence < 0.45;
    }

    protected function detectIntent(string $normalizedMessage): string
    {
        foreach ($this->memoryPatterns as $pattern => $intent) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalizedMessage, $normPattern)) {
                return $intent;
            }
        }

        foreach ($this->projectStructurePatterns as $pattern => $intent) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalizedMessage, $normPattern)) {
                return $intent;
            }
        }

        foreach ($this->getMergedKnowledgePatterns() as $pattern => $intent) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalizedMessage, $normPattern)) {
                return $intent;
            }
        }

        foreach ($this->getMergedLiveDataPatterns() as $pattern => $intent) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalizedMessage, $normPattern)) {
                return in_array($intent, ['count', 'list', 'latest']) ? 'live_data' : 'analytics';
            }
        }

        return 'direct';
    }

    protected function matchTools(string $normalizedMessage, array $availableTools): array
    {
        $toolPool = ! empty($availableTools) ? $availableTools : $this->toolRegistry->all();
        $threshold = $this->toolMatchThreshold();
        $maxTools = (int) config('ai-chat.context.max_tools', 5);

        $searchResults = $this->getSearchIndex()->search($normalizedMessage, $maxTools);
        $scoredTools = [];

        foreach ($searchResults as $result) {
            if ($result['score'] >= $threshold) {
                $scoredTools[$result['name']] = $result['score'];
            }
        }

        $directMatchTools = $this->matchToolsDirect($normalizedMessage, $toolPool);

        foreach ($directMatchTools as $name => $score) {
            if (! isset($scoredTools[$name]) || $score > $scoredTools[$name]) {
                $scoredTools[$name] = $score;
            }
        }

        arsort($scoredTools);

        return array_slice(array_keys($scoredTools), 0, $maxTools);
    }

    protected function matchToolsDirect(string $normalizedMessage, array $toolPool): array
    {
        $threshold = $this->toolMatchThreshold();
        $scored = [];

        foreach ($toolPool as $tool) {
            $name = mb_strtolower($tool->name());
            $nameNormalized = ArabicTextNormalizer::normalize($name);

            if ($normalizedMessage === $nameNormalized || $normalizedMessage === $name) {
                $scored[$name] = 1.00;

                continue;
            }

            if (str_contains($normalizedMessage, $nameNormalized) || str_contains($normalizedMessage, $name)) {
                $scored[$name] = 0.85;

                continue;
            }

            $score = $this->scoreToolByParts($tool, $normalizedMessage);

            if ($score >= $threshold) {
                $scored[$name] = $score;
            }
        }

        return $scored;
    }

    protected function scoreToolByParts(mixed $tool, string $normalizedMessage): float
    {
        $name = mb_strtolower($tool->name());
        $toolParts = explode('_', $name);
        $modelWord = $toolParts[0] ?? '';

        if ($modelWord === '') {
            return 0.0;
        }

        $normalizedModel = ArabicTextNormalizer::normalize($modelWord);

        if (! str_contains($normalizedMessage, $normalizedModel) && ! str_contains($normalizedMessage, $modelWord)) {
            return 0.0;
        }

        $actionParts = array_slice($toolParts, 1);
        $actionWord = $actionParts[0] ?? '';

        if ($actionWord === '') {
            return 0.0;
        }

        $dialectSynonyms = $this->getActionSynonyms($actionWord);

        foreach ($dialectSynonyms as $synonym) {
            $normSynonym = ArabicTextNormalizer::normalize($synonym);
            if (str_contains($normalizedMessage, $normSynonym)) {
                return 0.80;
            }
        }

        $keywords = method_exists($tool, 'keywords') ? $tool->keywords() : [];

        foreach ($keywords as $keyword) {
            $normKeyword = ArabicTextNormalizer::normalize($keyword);
            if ($normKeyword !== '' && str_contains($normalizedMessage, $normKeyword)) {
                return 0.85;
            }
        }

        $tags = method_exists($tool, 'tags') ? $tool->tags() : [];

        foreach ($tags as $tag) {
            $tagWords = explode('_', str_replace('-', '_', ArabicTextNormalizer::normalize($tag)));
            $matches = 0;

            foreach ($tagWords as $word) {
                if (mb_strlen($word) > 3 && str_contains($normalizedMessage, $word)) {
                    $matches++;
                }
            }

            if ($matches >= 2) {
                return 0.70;
            }
        }

        return 0.0;
    }

    protected function toolMatchesMessage(mixed $tool, string $normalizedMessage): bool
    {
        $score = $this->scoreToolMatch($tool, $normalizedMessage);

        return $score >= $this->toolMatchThreshold();
    }

    protected function scoreToolMatch(mixed $tool, string $normalizedMessage): float
    {
        $name = mb_strtolower($tool->name());
        $nameNormalized = ArabicTextNormalizer::normalize($name);

        if ($normalizedMessage === $nameNormalized || $normalizedMessage === $name) {
            return 1.00;
        }

        if (str_contains($normalizedMessage, $nameNormalized) || str_contains($normalizedMessage, $name)) {
            return 0.85;
        }

        return $this->scoreToolByParts($tool, $normalizedMessage);
    }

    protected function bestToolScore(array $toolNames, ?string $query = null): float
    {
        if (empty($toolNames)) {
            return 0.0;
        }

        if ($query === null) {
            return 1.00;
        }

        $searchResults = $this->getSearchIndex()->search($query, 100);
        $scores = [];

        foreach ($searchResults as $result) {
            $scores[$result['name']] = $result['score'];
        }

        $max = 0.0;

        foreach ($toolNames as $name) {
            $max = max($max, $scores[$name] ?? 0.0);
        }

        return $max;
    }

    protected function isKnowledgePattern(string $normalizedMessage): bool
    {
        foreach ($this->getMergedKnowledgePatterns() as $pattern => $type) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalizedMessage, $normPattern)) {
                return true;
            }
        }

        return false;
    }

    protected function isMemoryPattern(string $normalizedMessage): bool
    {
        foreach ($this->memoryPatterns as $pattern => $type) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalizedMessage, $normPattern)) {
                return true;
            }
        }

        return false;
    }

    protected function isComplexAnalytics(string $normalizedMessage): bool
    {
        foreach ($this->complexAnalyticsIndicators as $indicator) {
            $normIndicator = ArabicTextNormalizer::normalize($indicator);
            if (str_contains($normalizedMessage, $normIndicator)) {
                return true;
            }
        }

        return false;
    }

    protected function hasBusinessRuleIndicator(string $normalizedMessage): bool
    {
        foreach ($this->businessRuleIndicators as $indicator) {
            $normIndicator = ArabicTextNormalizer::normalize($indicator);
            if (str_contains($normalizedMessage, $normIndicator)) {
                return true;
            }
        }

        return false;
    }

    protected function detectGreeting(string $normalizedMessage, string $originalMessage): ?array
    {
        $hash = md5($normalizedMessage);

        if (array_key_exists($hash, $this->greetingCache)) {
            return $this->greetingCache[$hash];
        }

        foreach ($this->greetingPatterns as $greeting) {
            $normGreeting = ArabicTextNormalizer::normalize($greeting);

            if ($normalizedMessage === $normGreeting) {
                $result = ['confidence' => 0.95, 'reason' => 'greeting_exact_match'];
                $this->greetingCache[$hash] = $result;

                return $result;
            }
        }

        $dialectGreetings = $this->getDialectGreetings();

        foreach ($dialectGreetings as $greeting) {
            $normGreeting = ArabicTextNormalizer::normalize($greeting);

            if ($normalizedMessage === $normGreeting) {
                $result = ['confidence' => 0.95, 'reason' => 'greeting_dialect_exact_match'];
                $this->greetingCache[$hash] = $result;

                return $result;
            }
        }

        $hasGreetingWord = false;

        foreach ($this->greetingPatterns as $greeting) {
            $normGreeting = ArabicTextNormalizer::normalize($greeting);
            if ($normGreeting !== '' && str_contains($normalizedMessage, $normGreeting)) {
                $hasGreetingWord = true;

                break;
            }
        }

        if (! $hasGreetingWord) {
            foreach ($dialectGreetings as $greeting) {
                $normGreeting = ArabicTextNormalizer::normalize($greeting);
                if ($normGreeting !== '' && str_contains($normalizedMessage, $normGreeting)) {
                    $hasGreetingWord = true;

                    break;
                }
            }
        }

        if ($hasGreetingWord && $this->isShortCasualPhrase($normalizedMessage)) {
            $result = ['confidence' => 0.90, 'reason' => 'greeting_phrase_match'];
            $this->greetingCache[$hash] = $result;

            return $result;
        }

        $this->greetingCache[$hash] = null;

        return null;
    }

    protected function isGreeting(string $normalizedMessage): bool
    {
        return $this->detectGreeting($normalizedMessage, $normalizedMessage) !== null;
    }

    protected function isShortCasualPhrase(string $normalizedMessage): bool
    {
        $wordCount = count(explode(' ', $normalizedMessage));

        if ($wordCount <= 3) {
            return true;
        }

        if ($wordCount <= 6) {
            $nonStopWords = array_filter(
                explode(' ', $normalizedMessage),
                fn (string $w) => mb_strlen($w) > 2 && ! in_array($w, ['يا', 'او', 'ولا', 'و']),
            );

            return count($nonStopWords) <= 3;
        }

        return false;
    }

    protected function checkMemory(ExecutionPlan $plan, string $normalizedMessage, string $originalMessage, array $matchedTools): void
    {
        if (! $this->isMemoryPattern($normalizedMessage)) {
            return;
        }

        $plan->useMemory = true;
        $plan->memoryQuery = $originalMessage;
        $plan->intent = 'memory';

        if (! empty($matchedTools)) {
            $plan->tools = $matchedTools;
            $plan->intent = 'mixed';
        }
    }

    protected function checkKnowledge(ExecutionPlan $plan, string $normalizedMessage, string $originalMessage, string $detectedIntent, array $matchedTools): void
    {
        $isKnowledge = false;
        $isProjectStructure = false;

        foreach ($this->projectStructurePatterns as $pattern => $type) {
            $normPattern = ArabicTextNormalizer::normalize($pattern);
            if (str_contains($normalizedMessage, $normPattern)) {
                $isProjectStructure = true;

                break;
            }
        }

        if (! $isProjectStructure) {
            $isKnowledge = $this->isKnowledgePattern($normalizedMessage);
        }

        if (! $isKnowledge && ! $isProjectStructure) {
            return;
        }

        $plan->useRag = true;
        $plan->ragQuery = $originalMessage;

        if ($isProjectStructure) {
            $plan->intent = 'project_structure';
        } else {
            $plan->intent = 'knowledge';
        }

        if (! empty($matchedTools)) {
            $plan->tools = $matchedTools;
            $plan->intent = 'mixed';
        }
    }

    protected function getMergedLiveDataPatterns(): array
    {
        return $this->mergeWithConfig($this->liveDataPatterns, 'ai-chat-dialects.live_data');
    }

    protected function getMergedKnowledgePatterns(): array
    {
        $merged = $this->knowledgePatterns;

        $projectStructure = config('ai-chat-dialects.project_structure', []);

        foreach ($projectStructure as $pattern) {
            $merged[$pattern] = 'project_structure';
        }

        $knowledge = config('ai-chat-dialects.knowledge', []);

        foreach ($knowledge as $pattern) {
            if (! isset($merged[$pattern])) {
                $merged[$pattern] = 'knowledge';
            }
        }

        return $merged;
    }

    protected function mergeWithConfig(array $classPatterns, string $configKey): array
    {
        $configPatterns = config($configKey, []);

        foreach ($configPatterns as $category => $patterns) {
            if (! is_array($patterns)) {
                continue;
            }

            foreach ($patterns as $pattern => $type) {
                if (is_int($pattern) && is_string($type)) {
                    if (! in_array($type, $classPatterns)) {
                        $classPatterns[$type] = 'count';
                    }
                } elseif (is_string($pattern) && is_string($type)) {
                    $classPatterns[$pattern] = $type;
                }
            }
        }

        return $classPatterns;
    }

    protected function getDialectGreetings(): array
    {
        if (! empty($this->dialectCache)) {
            return $this->dialectCache;
        }

        $greetings = config('ai-chat-dialects.greetings', []);

        foreach ($greetings as $dialect => $patterns) {
            if (is_array($patterns)) {
                $this->dialectCache = array_merge($this->dialectCache, $patterns);
            }
        }

        return $this->dialectCache;
    }

    protected function getActionSynonyms(string $action): array
    {
        $dialectSynonyms = config('ai-chat-dialects.action_synonyms', []);

        if (isset($dialectSynonyms[$action])) {
            return $dialectSynonyms[$action];
        }

        $builtin = [
            'count' => ['how many', 'number of', 'count of', 'total', 'كم', 'عدد', 'كام', 'اجمالي', 'مجموع'],
            'search' => ['search', 'find', 'look for', 'lookup', 'filter', 'where', 'بحث', 'ابحث', 'دور', 'فتش', 'هات', 'اظهر'],
            'latest' => ['latest', 'recent', 'newest', 'last', 'new', 'اخر', 'احدث', 'جديد'],
            'stats' => ['stats', 'statistics', 'analytics', 'overview', 'احصائيات', 'تحليلات'],
            'summary' => ['summary', 'summarize', 'overview', 'average', 'avg', 'ملخص', 'لخص'],
            'top' => ['top', 'highest', 'most', 'best', 'اكتر', 'اكثر', 'اعلى', 'افضل'],
        ];

        return $builtin[$action] ?? [];
    }

    protected function getSearchIndex(): ToolSearchIndex
    {
        if ($this->searchIndex !== null) {
            return $this->searchIndex;
        }

        $tools = $this->toolRegistry->all();

        $this->searchIndex = new ArrayToolSearchIndex;
        $this->searchIndex->index($tools);

        return $this->searchIndex;
    }

    protected function toolMatchThreshold(): float
    {
        return (float) config('ai-chat.planning.tool_match_threshold', 0.70);
    }
}
