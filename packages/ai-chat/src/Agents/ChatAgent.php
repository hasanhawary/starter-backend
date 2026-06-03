<?php

namespace AiChat\Agents;

use AiChat\Chat\HistoryPolicy;
use AiChat\Chat\HistorySelector;
use AiChat\Contracts\HasProviderOptions;
use AiChat\MCP\ToolAdapter;
use AiChat\MCP\ToolRegistry;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Prompt\OutputContract;
use AiChat\Storage\AnonymousConversationStore;
use AiChat\Support\ArabicTextNormalizer;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('glm')]
#[Model('glm-1.5')]
#[MaxTokens(65536)]
#[Temperature(1.0)]
#[Timeout(300)]
class ChatAgent implements Agent, Conversational, HasMiddleware, HasProviderOptions, HasTools
{
    use Promptable;

    protected ?string $conversationId = null;

    protected ?string $sessionId = null;

    protected ?string $systemPrompt = null;

    protected array $enabledTools = [];

    protected int $historyLimit = 100;

    protected ?HistoryPolicy $historyPolicy = null;

    protected ?ExecutionPlan $executionPlan = null;

    protected ?string $currentMessage = null;

    protected ?string $storedContext = null;

    protected bool $contextPrepared = false;

    protected array $toolContextPayload = [];

    public function __construct(?string $systemPrompt = null)
    {
        $this->systemPrompt = $systemPrompt ?? config('ai-chat.conversations.default_system_prompt');
    }

    public function forSession(string $sessionId): static
    {
        $this->sessionId = $sessionId;
        $this->conversationId = null;

        return $this;
    }

    public function continue(string $conversationId, string $sessionId): static
    {
        $this->conversationId = $conversationId;
        $this->sessionId = $sessionId;

        return $this;
    }

    public function withSystemPrompt(string $prompt): static
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    public function withTools(array $toolNames): static
    {
        $this->enabledTools = $toolNames;

        return $this;
    }

    public function withHistoryLimit(int $limit): static
    {
        $this->historyLimit = $limit;

        return $this;
    }

    public function withHistoryPolicy(HistoryPolicy $policy): static
    {
        $this->historyPolicy = $policy;

        return $this;
    }

    public function withExecutionPlan(ExecutionPlan $plan): static
    {
        $this->executionPlan = $plan;

        return $this;
    }

    public function withCurrentMessage(string $message): static
    {
        $this->currentMessage = $message;

        return $this;
    }

    public function withToolContextPayload(array $payload): static
    {
        $this->toolContextPayload = $payload;

        return $this;
    }

    public function instructions(): Stringable|string
    {
        $this->prepareContextIfNeeded();

        $policy = $this->resolveHistoryPolicy();

        $parts = [];

        if ($this->systemPrompt) {
            $parts[] = $this->systemPrompt;
        }

        $parts[] = OutputContract::rule();

        $plan = $this->executionPlan;

        if ($plan === null) {
            $parts[] = 'The user is greeting, thanking, or asking about your identity. Reply naturally and very briefly — a friendly one-liner is enough. Do not use tools, RAG, or memory. Do not mention any previous conversation. If greeting in Arabic, respond in Arabic briefly. If the user thanks you, respond simply with "عفواً" or "you\'re welcome". If asking your name, say "أنا مساعدك الذكي" or "I am your AI assistant."';
        } elseif ($plan->needsClarification) {
            $parts[] = 'The user\'s intent is unclear. Ask a short clarifying question to understand what they need. Do not run tools, RAG, or memory. Do not guess their intent.';
        } elseif ($plan->intent === 'summary') {
            $parts[] = 'The user is asking for a conversation summary. Use the full conversation history to provide a brief summary of all key topics discussed. Cover the main points only. Do not re-answer any individual questions.';
        } elseif ($plan->useMemory) {
            $parts[] = 'The user is asking about a previously discussed fact, like their name or a past topic. Use the relevant context from previous conversation to answer. If you don\'t find the information in the context, say so briefly. Answer directly and concisely.';
        } elseif ($plan->useRag) {
            $parts[] = 'The user is asking about project documentation, business rules, or policies. Answer only from the retrieved knowledge context below. If the knowledge context doesn\'t contain the answer, say: "I don\'t have enough information about that in the project knowledge." Do not invent or guess policies. Do not use tools or general knowledge to answer project-specific questions. Do not mention the knowledge base, sources, source numbers, retrieved context, or how the answer was derived. Return only the final answer.';
        } elseif (! empty($plan->tools)) {
            $parts[] = OutputContract::toolRule();
        } elseif ($plan->intent === 'direct' && $plan->historyMode !== 'none') {
            $parts[] = 'The user is continuing from the previous topic. Use the recent conversation history to continue naturally, but only answer the latest user message. Do not re-answer old questions. Keep the same context and format as the previous assistant response.';
        } else {
            $parts[] = 'The user is greeting, thanking, or asking about your identity. Reply naturally and very briefly — a friendly one-liner is enough. Do not use tools, RAG, or memory. Do not mention any previous conversation. If greeting in Arabic, respond in Arabic briefly. If the user thanks you, respond simply with "عفواً" or "you\'re welcome". If asking your name, say "أنا مساعدك الذكي" or "I am your AI assistant."';
        }

        if ($policy->mode === 'relevant' && $this->storedContext) {
            $parts[] = "Relevant context from previous conversation:\n{$this->storedContext}";
        }

        return trim(implode("\n\n", array_filter($parts)));
    }

    public function messages(): iterable
    {
        if (! $this->conversationId) {
            return [];
        }

        $policy = $this->resolveHistoryPolicy();

        if (! $policy->useHistory) {
            $this->storedContext = null;

            return [];
        }

        $allMessages = $this->getAllMessages();

        if (empty($allMessages)) {
            return [];
        }

        return match ($policy->mode) {
            'relevant' => [],
            'recent' => array_slice($allMessages, -max($policy->limit, 1)),
            'summary' => $allMessages,
            default => [],
        };
    }

    public function tools(): iterable
    {
        $registry = app(ToolRegistry::class);
        $allTools = $registry->all();

        if ($this->enabledTools) {
            $resolved = [];

            foreach ($this->enabledTools as $name) {
                if (isset($allTools[$name])) {
                    $resolved[] = new ToolAdapter($allTools[$name], $this->toolContextPayload);
                }
            }

            return $resolved;
        }

        return [];
    }

    public function currentConversation(): ?string
    {
        return $this->conversationId;
    }

    public function sessionId(): ?string
    {
        return $this->sessionId;
    }

    public function middleware(): array
    {
        return [];
    }

    public function providerOptions(Lab|string $provider): array
    {
        if (config('ai-chat.thinking.enabled', true)) {
            return [
                'thinking' => [
                    'type' => config('ai-chat.thinking.type', 'enabled'),
                ],
            ];
        }

        return [];
    }

    public function model(): string
    {
        return config('ai-chat.model', 'gpt-5.4');
    }

    public function provider(): string
    {
        return config('ai-chat.provider', 'openai');
    }

    protected function handleRelevantMode(array $allMessages, HistoryPolicy $policy): array
    {
        $filtered = $this->filterRelevant($allMessages, $policy->query, $policy->limit);

        if (empty($filtered)) {
            $this->storedContext = null;

            return [];
        }

        $lines = [];
        foreach ($filtered as $message) {
            $role = $this->extractRole($message);
            $content = $this->extractContent($message);
            if ($content !== '' && $role !== null) {
                $lines[] = "[{$role}] {$content}";
            }
        }

        if (! empty($lines)) {
            $this->storedContext = implode("\n", $lines);
        }

        return [];
    }

    protected function prepareContextIfNeeded(): void
    {
        if ($this->contextPrepared || ! $this->conversationId) {
            return;
        }

        $this->contextPrepared = true;

        $policy = $this->resolveHistoryPolicy();

        if ($policy->mode !== 'relevant') {
            return;
        }

        $allMessages = $this->getAllMessages();

        if (empty($allMessages)) {
            return;
        }

        $this->handleRelevantMode($allMessages, $policy);
    }

    protected function getAllMessages(): array
    {
        return $this->conversationStore()
            ->getLatestConversationMessages(
                $this->conversationId,
                min($this->historyLimit, config('ai-chat.conversations.max_messages', 100)),
            )->all();
    }

    protected function resolveHistoryPolicy(): HistoryPolicy
    {
        if ($this->historyPolicy) {
            return $this->historyPolicy;
        }

        if ($this->executionPlan && $this->currentMessage) {
            $selector = app(HistorySelector::class);

            return $selector->select($this->executionPlan, $this->currentMessage);
        }

        if ($this->executionPlan) {
            $mode = $this->executionPlan->historyMode;

            if ($mode === 'none') {
                return HistoryPolicy::none();
            }

            if ($mode === 'summary') {
                return HistoryPolicy::summary();
            }

            if ($mode === 'relevant') {
                return HistoryPolicy::relevant(
                    $this->executionPlan->memoryQuery ?? '',
                    $this->executionPlan->historyLimit,
                );
            }

            return HistoryPolicy::recent($this->executionPlan->historyLimit);
        }

        return HistoryPolicy::recent($this->historyLimit);
    }

    protected function filterRelevant(array $messages, string $query, int $limit): array
    {
        $normalizedQuery = ArabicTextNormalizer::normalize($query);
        $queryWords = array_filter(
            explode(' ', $normalizedQuery),
            fn (string $w) => mb_strlen($w) > 2,
        );

        if (empty($queryWords)) {
            return array_slice($messages, -$limit);
        }

        $scored = [];

        foreach ($messages as $message) {
            $content = $this->extractContent($message);
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
            return array_slice($messages, -$limit);
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'message'), 0, $limit);
    }

    protected function extractContent(mixed $message): string
    {
        if (is_array($message)) {
            return (string) ($message['content'] ?? '');
        }

        if (is_object($message) && property_exists($message, 'content')) {
            return (string) $message->content;
        }

        return (string) $message;
    }

    protected function extractRole(mixed $message): ?string
    {
        if (is_array($message)) {
            return $message['role'] ?? null;
        }

        if (is_object($message) && property_exists($message, 'role')) {
            $role = $message->role;

            return $role instanceof MessageRole ? $role->value : (string) $role;
        }

        return null;
    }

    protected function conversationStore(): ConversationStore
    {
        return app(AnonymousConversationStore::class);
    }
}
