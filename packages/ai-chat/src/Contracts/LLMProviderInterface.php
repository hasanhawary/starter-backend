<?php

namespace AiChat\Contracts;

interface LLMProviderInterface
{
    public function send(array $messages, array $tools = [], array $options = []): array;

    public function stream(array $messages, array $tools = [], array $options = []): \Generator;

    public function name(): string;
}
