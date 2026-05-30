<?php

namespace AiChat\Policies;

use AiChat\Contracts\AgentInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class ChatContext
{
    public function __construct(
        public readonly string $action,
        public readonly ?AgentInterface $agent = null,
        public readonly ?Authenticatable $user = null,
        public readonly array $payload = [],
    ) {}

    public function isWriteAction(): bool
    {
        return in_array($this->action, $this->writeActions());
    }

    protected function writeActions(): array
    {
        return config('ai-chat.policies.write_actions', [
            'create',
            'update',
            'delete',
            'write',
            'modify',
            'execute',
        ]);
    }
}
