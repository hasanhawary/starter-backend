<?php

namespace AiChat\Agents;

use AiChat\Contracts\HasProviderOptions;
use AiChat\MCP\ToolAdapter;
use AiChat\MCP\ToolRegistry;
use AiChat\Middleware\ManageAnonymousConversation;
use AiChat\Storage\AnonymousConversationStore;
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

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function messages(): iterable
    {
        if (! $this->conversationId || $this->historyLimit <= 0) {
            return [];
        }

        return $this->conversationStore()
            ->getLatestConversationMessages(
                $this->conversationId,
                min($this->historyLimit, config('ai-chat.conversations.max_messages', 100)),
            )->all();
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
        return config('ai-chat.model', 'glm-5.1');
    }

    protected function conversationStore(): ConversationStore
    {
        return app(AnonymousConversationStore::class);
    }
}
