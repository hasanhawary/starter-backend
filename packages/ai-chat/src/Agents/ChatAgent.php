<?php

namespace AiChat\Agents;

use AiChat\Chat\HistoryPolicy;
use AiChat\Chat\HistorySelector;
use AiChat\Contracts\HasProviderOptions;
use AiChat\MCP\ToolAdapter;
use AiChat\MCP\ToolRegistry;
use AiChat\Middleware\ManageAnonymousConversation;
use AiChat\Pipeline\ExecutionPlan;
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
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('glm')]
#[Model('glm-5.1')]
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

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function messages(): iterable
    {
        if (! $this->conversationId) {
            return [];
        }

        $policy = $this->resolveHistoryPolicy();

        if (! $policy->useHistory) {
            return [];
        }

        $allMessages = $this->conversationStore()
            ->getLatestConversationMessages(
                $this->conversationId,
                min($this->historyLimit, config('ai-chat.conversations.max_messages', 100)),
            )->all();

        if (empty($allMessages)) {
            return [];
        }

        if ($policy->mode === 'recent') {
            return array_slice($allMessages, -max($policy->limit, 1));
        }

        if ($policy->mode === 'relevant' && $policy->query) {
            return $this->filterRelevant($allMessages, $policy->query, $policy->limit);
        }

        return $allMessages;
    }

    public function tools(): iterable
    {
        $registry = app(ToolRegistry::class);
        $allTools = $registry->all();

        if ($this->enabledTools) {
            $resolved = [];

            foreach ($this->enabledTools as $name) {
                if (isset($allTools[$name])) {
                    $resolved[] = new ToolAdapter($allTools[$name]);
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
        return [
            new ManageAnonymousConversation($this->conversationStore()),
        ];
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

        if (is_object($message) && method_exists($message, 'content')) {
            return (string) $message->content();
        }

        if (is_object($message) && property_exists($message, 'content')) {
            return (string) $message->content;
        }

        return (string) $message;
    }

    protected function conversationStore(): ConversationStore
    {
        return app(AnonymousConversationStore::class);
    }
}
