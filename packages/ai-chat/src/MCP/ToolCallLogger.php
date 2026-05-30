<?php

namespace AiChat\MCP;

use AiChat\Policies\ChatContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ToolCallLogger
{
    public function log(string $toolName, array $arguments, ToolResult $result, ChatContext $context, float $durationMs): void
    {
        $channel = config('ai-chat.tools.logging', 'database');

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
                'tool' => $entry['tool'],
                'arguments' => json_encode($entry['arguments']),
                'success' => $entry['success'],
                'error' => $entry['error'],
                'duration_ms' => $entry['duration_ms'],
                'user_id' => $entry['user_id'],
                'agent' => $entry['agent'],
                'payload' => json_encode($entry['payload']),
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
        Log::channel(config('ai-chat.tools.log_channel', 'stack'))->info('AI Tool Call', $entry);
    }
}
