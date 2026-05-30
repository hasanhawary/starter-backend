<?php

namespace AiChat\Pipeline;

use AiChat\Contracts\AgentInterface;
use AiChat\MCP\ToolResult;
use AiChat\Models\AiChatConversation;
use Illuminate\Contracts\Auth\Authenticatable;

class ChatPayload
{
    public string $message;

    public ?Authenticatable $user = null;

    public ?AiChatConversation $conversation = null;

    public ?AgentInterface $agent = null;

    public array $context = [];

    public array $toolResults = [];

    public ?string $response = null;

    public array $metadata = [];

    public array $errors = [];

    public array $tools = [];

    public array $knowledge = [];

    public array $memory = [];

    public array $messages = [];

    public ?array $rawResponse = null;

    public bool $streaming = false;

    public function __construct(string $message, ?Authenticatable $user = null)
    {
        $this->message = $message;
        $this->user = $user;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function addError(string $error, int $code = 400): self
    {
        $this->errors[] = compact('error', 'code');

        return $this;
    }

    public function setContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    public function getContext(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    public function addToolResult(string $toolName, ToolResult $result): self
    {
        $this->toolResults[$toolName] = $result;

        return $this;
    }

    public function setMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    public function firstError(): ?string
    {
        return $this->errors[0]['error'] ?? null;
    }

    public function firstErrorCode(): int
    {
        return $this->errors[0]['code'] ?? 500;
    }

    public function conversationId(): ?string
    {
        return $this->conversation?->id;
    }
}
