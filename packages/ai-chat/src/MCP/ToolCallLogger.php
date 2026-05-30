<?php

namespace AiChat\MCP;

use AiChat\Policies\ChatContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ToolCallLogger
{
    public function log(string $toolName, array $arguments, ToolResult $result, ChatContext $context, float $durationMs): void
    {
        $channel = config('ai-chat.tool_logging.driver', 'database');

        if ($channel === 'none') {
            return;
        }

        $entry = [
            'tool' => $toolName,
            'arguments' => $arguments,
            'success' => $result->success,
            'error' => $result->error,
            'duration_ms' => round($durationMs, 2),
            'user_id' => $context->user?->getAuthIdentifier(),
            'agent' => $context->agent?->name(),
            'payload' => $context->payload,
            'logged_at' => now()->toIso8601String(),
        ];

        if ($channel === 'database' || $channel === 'both') {
            $this->logToDatabase($entry);
        }

        if ($channel === 'log' || $channel === 'both') {
            $this->logToFile($entry);
        }
    }

    protected function logToDatabase(array $entry): void
    {
        try {
            DB::table('ai_tool_calls')->insert([
                'id' => (string) Str::uuid7(),
                'conversation_id' => $entry['payload']['conversation_id'] ?? null,
                'message_id' => null,
                'tool_name' => $entry['tool'],
                'arguments' => json_encode($entry['arguments']),
                'result' => $entry['success'] ? json_encode($entry) : null,
                'status' => $entry['success'] ? 'success' : 'failed',
                'duration_ms' => (int) $entry['duration_ms'],
                'error' => $entry['error'],
                'user_id' => $entry['user_id'],
                'tenant_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log tool call to database', [
                'tool' => $entry['tool'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function logToFile(array $entry): void
    {
        Log::channel(config('ai-chat.tool_logging.log_channel', 'stack'))->info('AI Tool Call', $entry);
    }
}
