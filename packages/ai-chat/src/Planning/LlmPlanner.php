<?php

namespace AiChat\Planning;

use AiChat\Pipeline\ExecutionPlan;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\AiManager;

class LlmPlanner
{
    protected string $systemPrompt = <<<'PROMPT'
You are a request planning engine for an AI chat system. Analyze the user message and return a JSON plan.

Return ONLY valid JSON, no markdown, no explanation. Your response must parse as valid JSON.

The intent must be one of: live_data, knowledge, memory, project_structure, mixed, direct, clarification

Rules:
- "live_data": User needs real-time data from tools (counts, lists, stats)
- "knowledge": User needs information from documentation/knowledge base
- "memory": User refers to previous conversations or past discussions
- "project_structure": User asks about code architecture, files, services
- "mixed": User needs both live data AND knowledge/memory
- "direct": Simple question that can be answered directly or with minimal context
- "clarification": The request is too ambiguous and needs more info

For tools: only include tool names that are relevant to this specific question.
For use_rag: set true ONLY if user asks about policies, rules, docs, explanations.
For use_memory: set true ONLY if user references previous conversations or past reports.

Be conservative. Default to minimal context. When in doubt, prefer "direct" with fewer tools.
PROMPT;

    public function __construct() {}

    public function plan(string $message, array $availableToolNames = [], ?string $locale = null): ?ExecutionPlan
    {
        $plannerModel = config('ai-chat.planning.planner_model');
        $providerConfig = config('ai-chat.providers.'.config('ai-chat.provider', 'glm'));

        if (! $plannerModel && isset($providerConfig['models']['text']['cheapest'])) {
            $plannerModel = $providerConfig['models']['text']['cheapest'];
        }

        if (! $plannerModel) {
            $plannerModel = 'glm-4-flash';
        }

        try {
            $response = $this->callLlm($message, $availableToolNames, $plannerModel);

            if (! $response) {
                return null;
            }

            return $this->parsePlan($response);
        } catch (\Throwable $e) {
            Log::warning('LLM Planner failed, falling back to null', [
                'error' => $e->getMessage(),
                'message' => mb_substr($message, 0, 100),
            ]);

            return null;
        }
    }

    protected function callLlm(string $message, array $availableToolNames, string $model): ?string
    {
        $aiManager = app(AiManager::class);
        $driver = $aiManager->driver();

        $toolListInfo = '';

        if (! empty($availableToolNames)) {
            $toolListInfo = "\n\nAvailable tools: ".implode(', ', $availableToolNames);
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt],
            ['role' => 'user', 'content' => "Plan this request: {$message}{$toolListInfo}\n\nRespond with JSON only."],
        ];

        $response = $driver->text($model, $messages);

        if (! $response) {
            return null;
        }

        $cleaned = trim(is_string($response) ? $response : ($response->text ?? (string) $response));

        if (str_starts_with($cleaned, '```json')) {
            $cleaned = substr($cleaned, 7);
        }

        if (str_starts_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 3);
        }

        if (str_ends_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 0, -3);
        }

        return trim($cleaned);
    }

    protected function parsePlan(string $response): ExecutionPlan
    {
        $data = json_decode($response, true);

        if (! is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('LLM Planner returned invalid JSON', ['response' => mb_substr($response, 0, 200)]);

            $plan = new ExecutionPlan;
            $plan->intent = 'direct';
            $plan->planner = 'llm_fallback';

            return $plan;
        }

        $plan = ExecutionPlan::fromArray($data);
        $plan->planner = 'llm';

        $validIntents = ['live_data', 'knowledge', 'memory', 'project_structure', 'mixed', 'direct', 'clarification'];

        if (! in_array($plan->intent, $validIntents)) {
            $plan->intent = 'direct';
        }

        if ($plan->ragLimit < 1 || $plan->ragLimit > 10) {
            $plan->ragLimit = 3;
        }

        if ($plan->memoryLimit < 1 || $plan->memoryLimit > 10) {
            $plan->memoryLimit = 3;
        }

        if ($plan->historyLimit < 2 || $plan->historyLimit > 20) {
            $plan->historyLimit = 6;
        }

        $maxTools = (int) config('ai-chat.context.max_tools', 5);

        if (count($plan->tools) > $maxTools) {
            $plan->tools = array_slice($plan->tools, 0, $maxTools);
        }

        return $plan;
    }
}
