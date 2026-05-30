<?php

namespace AiChat\MCP;

use AiChat\Policies\ChatContext;
use Throwable;

class ToolExecutor
{
    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly ToolPermissionGuard $guard,
        private readonly ToolInputValidator $validator,
        private readonly ToolOutputNormalizer $normalizer,
        private readonly ToolCallLogger $logger,
    ) {}

    public function execute(string $toolName, array $arguments, ChatContext $context): ToolResult
    {
        $start = microtime(true);

        $tool = $this->registry->get($toolName);

        if (! $tool) {
            return ToolResult::failure("Tool [{$toolName}] not found.");
        }

        $policyResult = $this->guard->check($tool, $context);

        if ($policyResult->isDenied()) {
            $duration = (microtime(true) - $start) * 1000;
            $result = ToolResult::failure($policyResult->reason, ['policy' => $policyResult->policy]);
            $this->logger->log($toolName, $arguments, $result, $context, $duration);

            return $result;
        }

        $schema = $tool->schema();

        try {
            $validated = $this->validator->validate($arguments, $schema);
        } catch (Throwable $e) {
            $duration = (microtime(true) - $start) * 1000;
            $result = ToolResult::failure('Input validation failed: '.$e->getMessage());
            $this->logger->log($toolName, $arguments, $result, $context, $duration);

            return $result;
        }

        try {
            $rawResult = $tool->execute($validated, $context);
        } catch (Throwable $e) {
            $duration = (microtime(true) - $start) * 1000;
            $result = ToolResult::failure('Tool execution failed: '.$e->getMessage());
            $this->logger->log($toolName, $validated, $result, $context, $duration);

            return $result;
        }

        $normalized = $this->normalizer->normalize($rawResult->data);
        $duration = (microtime(true) - $start) * 1000;

        $result = ToolResult::success($normalized, array_merge($rawResult->metadata, [
            'duration_ms' => round($duration, 2),
        ]));

        $this->logger->log($toolName, $validated, $result, $context, $duration);

        return $result;
    }
}
