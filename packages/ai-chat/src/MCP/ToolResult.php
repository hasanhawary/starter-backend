<?php

namespace AiChat\MCP;

class ToolResult
{
    public function __construct(
        public readonly bool $success,
        public readonly mixed $data = null,
        public readonly ?string $error = null,
        public readonly array $metadata = [],
    ) {}

    public static function success(mixed $data, array $metadata = []): self
    {
        return new self(true, $data, null, $metadata);
    }

    public static function failure(string $error, array $metadata = []): self
    {
        return new self(false, null, $error, $metadata);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error,
            'metadata' => $this->metadata,
        ];
    }
}
