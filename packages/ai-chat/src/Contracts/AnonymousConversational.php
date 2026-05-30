<?php

namespace AiChat\Contracts;

interface AnonymousConversational
{
    public function forSession(string $sessionId): static;

    public function continue(string $conversationId, string $sessionId): static;

    public function sessionId(): ?string;
}
