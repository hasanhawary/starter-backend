<?php

namespace AiChat\Planning;

use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ExecutionPlan;

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
        'list' => 'list',
        'show me' => 'list',
        'get all' => 'list',
        'latest' => 'latest',
        'recent' => 'latest',
        'newest' => 'latest',
        'last' => 'latest',
        'top' => 'analytics',
        'highest' => 'analytics',
        'lowest' => 'analytics',
        'most' => 'analytics',
        'compare' => 'analytics',
        'vs' => 'analytics',
        'versus' => 'analytics',
        'summary' => 'summary',
        'summarize' => 'summary',
        'stats' => 'analytics',
        'statistics' => 'analytics',
        'analytics' => 'analytics',
    ];

    protected array $knowledgePatterns = [
        'what are the' => 'knowledge',
        'what is the' => 'knowledge',
        'explain' => 'knowledge',
        'اشرح' => 'knowledge',
        'سياسة' => 'knowledge',
        'policy' => 'knowledge',
        'rules' => 'knowledge',
        'how does' => 'knowledge',
        'how do' => 'knowledge',
        'where is' => 'project_structure',
        'which file' => 'project_structure',
        'which service' => 'project_structure',
        'which controller' => 'project_structure',
        'what module' => 'project_structure',
        'architecture' => 'project_structure',
        'structure' => 'project_structure',
        'documentation' => 'knowledge',
        'docs' => 'knowledge',
        'readme' => 'knowledge',
    ];

    protected array $memoryPatterns = [
        'continue' => 'memory',
        'كمل' => 'memory',
        'as we discussed' => 'memory',
        'we talked about' => 'memory',
        'like before' => 'memory',
        'previous' => 'memory',
        'earlier' => 'memory',
        'last time' => 'memory',
        'report' => 'memory',
        'تقرير' => 'memory',
        'again' => 'memory',
    ];

    public function __construct(
        protected ToolRegistry $toolRegistry,
    ) {}

    public function plan(string $message, array $availableTools = [], ?string $locale = null): ExecutionPlan
    {
        $normalizedMessage = mb_strtolower(trim($message));
        $plan = new ExecutionPlan;
        $plan->planner = 'heuristic';

        $detectedIntent = $this->detectIntent($normalizedMessage);
        $plan->intent = $detectedIntent;

        $matchedTools = $this->matchTools($normalizedMessage, $availableTools);

        if ($this->isMemoryPattern($normalizedMessage)) {
            $plan->useMemory = true;
            $plan->memoryQuery = $message;
            $plan->intent = 'memory';

            if (! empty($matchedTools)) {
                $plan->tools = $matchedTools;
                $plan->intent = 'mixed';
            }

            return $plan;
        }

        if ($this->isKnowledgePattern($normalizedMessage)) {
            $plan->useRag = true;
            $plan->ragQuery = $message;

            if (in_array($detectedIntent, ['project_structure'])) {
                $plan->intent = 'project_structure';
            } else {
                $plan->intent = 'knowledge';
            }

            if (! empty($matchedTools)) {
                $plan->tools = $matchedTools;
                $plan->intent = 'mixed';
            }

            return $plan;
        }

        if (! empty($matchedTools)) {
            $plan->tools = $matchedTools;
            $plan->intent = 'live_data';

            if ($this->isComplexAnalytics($normalizedMessage)) {
                $plan->intent = 'mixed';
                $plan->useRag = true;
                $plan->ragQuery = $message;
            }

            return $plan;
        }

        $plan->intent = 'direct';

        return $plan;
    }

    public function canHandle(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));

        foreach ($this->liveDataPatterns as $pattern => $type) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        foreach ($this->knowledgePatterns as $pattern => $type) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        foreach ($this->memoryPatterns as $pattern => $type) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        $allTools = $this->toolRegistry->all();

        foreach ($allTools as $tool) {
            if ($this->toolMatchesMessage($tool, $normalized)) {
                return true;
            }
        }

        return false;
    }

    protected function detectIntent(string $normalizedMessage): string
    {
        foreach ($this->memoryPatterns as $pattern => $intent) {
            if (str_contains($normalizedMessage, $pattern)) {
                return $intent;
            }
        }

        foreach ($this->knowledgePatterns as $pattern => $intent) {
            if (str_contains($normalizedMessage, $pattern)) {
                return $intent;
            }
        }

        foreach ($this->liveDataPatterns as $pattern => $intent) {
            if (str_contains($normalizedMessage, $pattern)) {
                return in_array($intent, ['count', 'list', 'latest']) ? 'live_data' : 'analytics';
            }
        }

        return 'direct';
    }

    protected function matchTools(string $normalizedMessage, array $availableTools): array
    {
        $toolPool = ! empty($availableTools) ? $availableTools : $this->toolRegistry->all();
        $matched = [];
        $maxTools = (int) config('ai-chat.context.max_tools', 5);

        foreach ($toolPool as $tool) {
            if (count($matched) >= $maxTools) {
                break;
            }

            if ($this->toolMatchesMessage($tool, $normalizedMessage)) {
                $matched[] = $tool->name();
            }
        }

        return $matched;
    }

    protected function toolMatchesMessage(mixed $tool, string $normalizedMessage): bool
    {
        $name = mb_strtolower($tool->name());
        $description = mb_strtolower($tool->description());

        if (str_contains($normalizedMessage, $name)) {
            return true;
        }

        $toolParts = explode('_', $name);
        $modelWord = $toolParts[0] ?? '';

        if ($modelWord !== '' && str_contains($normalizedMessage, $modelWord)) {
            $actionParts = array_slice($toolParts, 1);
            $actionWord = $actionParts[0] ?? '';

            if ($actionWord !== '') {
                $actionMap = [
                    'count' => ['how many', 'number of', 'count of', 'total', 'كم', 'عدد'],
                    'search' => ['search', 'find', 'look for', 'lookup', 'filter', 'where', 'بحث', 'ابحث'],
                    'latest' => ['latest', 'recent', 'newest', 'last', 'new', 'اخر', 'أخر', 'احدث', 'أحدث'],
                    'stats' => ['stats', 'statistics', 'analytics', 'overview', 'إحصائيات'],
                    'summary' => ['summary', 'summarize', 'overview', 'average', 'avg', 'ملخص'],
                ];

                $actionSynonyms = $actionMap[$actionWord] ?? [];

                foreach ($actionSynonyms as $synonym) {
                    if (str_contains($normalizedMessage, $synonym)) {
                        return true;
                    }
                }
            }
        }

        $keywords = method_exists($tool, 'keywords') ? $tool->keywords() : [];

        foreach ($keywords as $keyword) {
            if (str_contains($normalizedMessage, mb_strtolower($keyword))) {
                return true;
            }
        }

        $tags = method_exists($tool, 'tags') ? $tool->tags() : [];

        foreach ($tags as $tag) {
            $tagWords = explode('_', str_replace('-', '_', $tag));

            foreach ($tagWords as $word) {
                if (strlen($word) > 3 && str_contains($normalizedMessage, $word)) {
                    return true;
                }
            }
        }

        $descriptionWords = preg_split('/[\s,.]+/', $description);

        foreach ($descriptionWords as $word) {
            if (strlen($word) > 5 && str_contains($normalizedMessage, $word)) {
                return true;
            }
        }

        return false;
    }

    protected function isKnowledgePattern(string $normalizedMessage): bool
    {
        foreach ($this->knowledgePatterns as $pattern => $type) {
            if (str_contains($normalizedMessage, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function isMemoryPattern(string $normalizedMessage): bool
    {
        foreach ($this->memoryPatterns as $pattern => $type) {
            if (str_contains($normalizedMessage, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function isComplexAnalytics(string $normalizedMessage): bool
    {
        $complexIndicators = ['compare', 'vs ', 'versus', 'difference between', 'relationship', 'correlation', 'trend'];

        foreach ($complexIndicators as $indicator) {
            if (str_contains($normalizedMessage, $indicator)) {
                return true;
            }
        }

        return false;
    }
}
