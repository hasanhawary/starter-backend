<?php

namespace AiChat\Planning;

use AiChat\Pipeline\ExecutionPlan;
use Illuminate\Support\Facades\Log;

use function Laravel\Ai\agent;

class LlmPlanner
{
    protected string $systemPrompt = <<<'PROMPT'
You are the production request planner for a Laravel AI chat system. Analyze the latest user message and return one executable JSON plan.

Return ONLY valid JSON, no markdown, no explanation. Your response must parse as valid JSON.

Allowed intent values:
- direct: greetings, thanks, general conversation, simple questions that need no app data, no memory, and no project knowledge
- live_data: live dashboard/application data, counts, lists, latest records, CRUD reads, analytics, reporting from tools
- knowledge: documentation, business rules, project knowledge, policies, architecture explanations
- project_structure: files, controllers, services, modules, code flow, implementation location questions
- memory: remembered user preferences, previous facts, previous conversations, user identity, prior discussion recall
- mixed: requires more than one capability, such as tools plus RAG, tools plus memory, or analytics plus business rules
- clarification: vague or incomplete request that cannot be safely planned

Rules:
- Use tools only for real application/database facts. Never guess counts, latest records, users, notifications, roles, settings, countries, analytics, revenue, orders, or CRUD data.
- Select only tool names that are explicitly listed in the available tools. If no relevant tool exists, leave tools empty and set intent to direct or clarification with a short clarification_question.
- Use RAG for project-specific knowledge, architecture, controllers, services, approval flows, authentication flow, business rules, or documentation questions.
- Use memory only when the user asks about remembered facts, preferences, identity, previous conversation, or asks to continue something from before.
- For mixed requests, enable every required capability. Example: "show weekly revenue like my preferred report" should use tools and memory.
- For destructive mutations, hidden fields, passwords, tokens, secrets, or unsafe requests, do not select tools. Use direct intent and set response_mode to safe_refusal.
- For ambiguous requests, set intent to clarification, needs_clarification to true, and provide clarification_question.
- Keep limits small and production-safe: rag_limit 1-5, memory_limit 1-5, history_limit 0-8 unless summarizing conversation.
- Prefer history_mode "none" for standalone/direct greetings, "recent" for follow-ups and tool/RAG requests, "relevant" for memory, and "summary" for conversation summaries.

Required JSON schema:
{
  "intent": "direct|live_data|knowledge|project_structure|memory|mixed|clarification",
  "capability": "optional short capability name or null",
  "tools": ["exact_available_tool_name"],
  "use_rag": true|false,
  "rag_query": "query for RAG or null",
  "rag_limit": 3,
  "use_memory": true|false,
  "memory_query": "query for memory or null",
  "memory_limit": 3,
  "history_limit": 0,
  "history_mode": "none|recent|relevant|summary",
  "needs_clarification": true|false,
  "clarification_question": "short question or null",
  "metadata": {
    "intent_analysis": "brief reason for classification",
    "execution_strategy": "direct_answer|tool_execution|memory_retrieval|rag_retrieval|agent_execution|multi_step|safe_refusal|clarification",
    "response_mode": "direct|tool_backed|memory_backed|rag_backed|mixed|safe_refusal|clarification",
    "requires_tools": true|false,
    "requires_memory": true|false,
    "requires_rag": true|false,
    "multi_step": true|false,
    "confidence": 0.0
  }
}

Be conservative. Default to minimal context. When in doubt, prefer "direct" with fewer tools.
PROMPT;

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

            $plan = $this->parsePlan($response, $availableToolNames, $message);

            Log::debug('LLM Planner produced execution plan', [
                'intent' => $plan->intent,
                'tools' => $plan->tools,
                'use_rag' => $plan->useRag,
                'use_memory' => $plan->useMemory,
                'history_mode' => $plan->historyMode,
                'planner' => $plan->planner,
                'reason' => $plan->metadata['intent_analysis'] ?? $plan->metadata['reason'] ?? null,
            ]);

            return $plan;
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
        $toolListInfo = '';

        if (! empty($availableToolNames)) {
            $toolListInfo = "\n\nAvailable tools:\n- ".implode("\n- ", $availableToolNames);
        }

        $response = agent(instructions: $this->systemPrompt)->prompt(
            "Plan this request: {$message}{$toolListInfo}\n\nRespond with JSON only.",
            provider: config('ai-chat.provider', 'glm'),
            model: $model,
            timeout: (int) config('ai-chat.planning.timeout', 120),
        );

        if (! $response) {
            return null;
        }

        return $this->cleanResponse(is_string($response) ? $response : ($response->text ?? (string) $response));
    }

    protected function parsePlan(string $response, array $availableToolNames = [], string $message = ''): ExecutionPlan
    {
        $cleaned = $this->cleanResponse($response);
        $data = json_decode($cleaned, true);

        if (! is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('LLM Planner returned invalid JSON', [
                'response' => mb_substr($response, 0, 500),
                'json_error' => json_last_error_msg(),
            ]);

            return $this->fallbackPlan('invalid_json', $message);
        }

        return $this->sanitizePlan(ExecutionPlan::fromArray($data), $availableToolNames, $message);
    }

    protected function cleanResponse(string $response): string
    {
        $cleaned = trim($response);

        if (str_starts_with($cleaned, '```json')) {
            $cleaned = substr($cleaned, 7);
        }

        if (str_starts_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 3);
        }

        if (str_ends_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 0, -3);
        }

        $cleaned = trim($cleaned);

        if (! str_starts_with($cleaned, '{')) {
            $jsonStart = strpos($cleaned, '{');
            $jsonEnd = strrpos($cleaned, '}');

            if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
                $cleaned = substr($cleaned, $jsonStart, $jsonEnd - $jsonStart + 1);
            }
        }

        return trim($cleaned);
    }

    protected function sanitizePlan(ExecutionPlan $plan, array $availableToolNames, string $message): ExecutionPlan
    {
        $validIntents = ['live_data', 'knowledge', 'memory', 'project_structure', 'mixed', 'direct', 'clarification', 'summary'];

        if (! in_array($plan->intent, $validIntents, true)) {
            $plan->intent = 'direct';
            $plan->metadata['sanitized_intent'] = true;
        }

        $plan->planner = 'llm';
        $plan->tools = $this->sanitizeTools($plan->tools, $availableToolNames);
        $plan->useRag = (bool) $plan->useRag;
        $plan->useMemory = (bool) $plan->useMemory;
        $plan->needsClarification = (bool) $plan->needsClarification;
        $plan->ragLimit = max(1, min((int) $plan->ragLimit, 5));
        $plan->memoryLimit = max(1, min((int) $plan->memoryLimit, 5));
        $plan->historyLimit = max(0, min((int) $plan->historyLimit, 20));

        if (! in_array($plan->historyMode, ['none', 'recent', 'relevant', 'summary'], true)) {
            $plan->historyMode = $plan->useMemory ? 'relevant' : 'recent';
        }

        if ($plan->intent === 'summary') {
            $plan->historyMode = 'summary';
            $plan->historyLimit = max($plan->historyLimit, 12);
        }

        if (in_array($plan->intent, ['knowledge', 'project_structure'], true)) {
            $plan->useRag = true;
            $plan->ragQuery ??= $message;
        }

        if ($plan->intent === 'memory') {
            $plan->useMemory = true;
            $plan->memoryQuery ??= $message;
            $plan->historyMode = 'relevant';
            $plan->historyLimit = max($plan->historyLimit, min($plan->memoryLimit, 8));
        }

        if ($plan->intent === 'mixed') {
            $plan->metadata['multi_step'] = true;
        }

        if ($plan->needsClarification || $plan->intent === 'clarification') {
            $plan->intent = 'clarification';
            $plan->needsClarification = true;
            $plan->tools = [];
            $plan->useRag = false;
            $plan->useMemory = false;
            $plan->historyMode = 'none';
            $plan->historyLimit = 0;
        }

        if ($plan->needsClarification && empty($plan->clarificationQuestion)) {
            $plan->clarificationQuestion = 'Could you clarify what you want me to help with?';
        }

        if ($plan->intent === 'direct' && empty($plan->tools) && ! $plan->useRag && ! $plan->useMemory) {
            $plan->historyLimit = min($plan->historyLimit, 2);

            if ($plan->historyLimit === 0) {
                $plan->historyMode = 'none';
            }
        }

        $metadata = $plan->metadata;
        $plan->metadata = array_merge($metadata, [
            'intent_analysis' => is_string($metadata['intent_analysis'] ?? null)
                ? $metadata['intent_analysis']
                : ($metadata['reason'] ?? 'llm_plan'),
            'execution_strategy' => is_string($metadata['execution_strategy'] ?? null)
                ? $metadata['execution_strategy']
                : $this->executionStrategy($plan),
            'response_mode' => is_string($metadata['response_mode'] ?? null)
                ? $metadata['response_mode']
                : $this->responseMode($plan),
            'requires_tools' => ! empty($plan->tools),
            'requires_memory' => $plan->useMemory,
            'requires_rag' => $plan->useRag,
            'multi_step' => (bool) ($metadata['multi_step'] ?? $plan->intent === 'mixed'),
            'confidence' => $this->sanitizeConfidence($metadata['confidence'] ?? null),
        ]);

        $plan->metadata['available_tool_count'] = count($availableToolNames);

        return $plan;
    }

    protected function sanitizeTools(array $tools, array $availableToolNames): array
    {
        $tools = array_values(array_unique(array_filter($tools, 'is_string')));

        if (! empty($availableToolNames)) {
            $available = array_flip($availableToolNames);
            $tools = array_values(array_filter($tools, fn (string $tool): bool => isset($available[$tool])));
        }

        return array_slice($tools, 0, (int) config('ai-chat.context.max_tools', 5));
    }

    protected function fallbackPlan(string $reason, string $message): ExecutionPlan
    {
        $plan = new ExecutionPlan;
        $plan->intent = 'direct';
        $plan->historyMode = 'none';
        $plan->historyLimit = 0;
        $plan->planner = 'llm_fallback';
        $plan->metadata = [
            'intent_analysis' => 'LLM planner response could not be parsed.',
            'execution_strategy' => 'agent_execution',
            'response_mode' => 'direct',
            'requires_tools' => false,
            'requires_memory' => false,
            'requires_rag' => false,
            'multi_step' => false,
            'confidence' => 0.0,
            'fallback_reason' => $reason,
            'message_excerpt' => mb_substr($message, 0, 120),
        ];

        return $plan;
    }

    protected function executionStrategy(ExecutionPlan $plan): string
    {
        if ($plan->needsClarification) {
            return 'clarification';
        }

        if (($plan->metadata['response_mode'] ?? null) === 'safe_refusal') {
            return 'safe_refusal';
        }

        if ($plan->intent === 'mixed') {
            return 'multi_step';
        }

        if (! empty($plan->tools)) {
            return 'tool_execution';
        }

        if ($plan->useMemory) {
            return 'memory_retrieval';
        }

        if ($plan->useRag) {
            return 'rag_retrieval';
        }

        return 'agent_execution';
    }

    protected function responseMode(ExecutionPlan $plan): string
    {
        if ($plan->needsClarification) {
            return 'clarification';
        }

        if ($plan->intent === 'mixed') {
            return 'mixed';
        }

        if (! empty($plan->tools)) {
            return 'tool_backed';
        }

        if ($plan->useMemory) {
            return 'memory_backed';
        }

        if ($plan->useRag) {
            return 'rag_backed';
        }

        return 'direct';
    }

    protected function sanitizeConfidence(mixed $confidence): float
    {
        if (! is_numeric($confidence)) {
            return 0.5;
        }

        return max(0.0, min((float) $confidence, 1.0));
    }
}
