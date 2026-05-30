<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiChatConversation;
use AiChat\Models\AiChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiChatMessageFactory extends Factory
{
    protected $model = AiChatMessage::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'conversation_id' => AiChatConversation::factory(),
            'session_id' => 'sess_'.Str::uuid(),
            'agent' => 'AiChat\Agents\ChatAgent',
            'role' => $this->faker->randomElement(['user', 'assistant']),
            'content' => $this->faker->paragraph(),
            'attachments' => null,
            'tool_calls' => null,
            'tool_results' => null,
            'usage' => null,
            'meta' => null,
        ];
    }

    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
        ]);
    }
}
