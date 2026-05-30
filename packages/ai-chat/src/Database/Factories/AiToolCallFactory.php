<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiChatConversation;
use AiChat\Models\AiChatMessage;
use AiChat\Models\AiToolCall;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiToolCallFactory extends Factory
{
    protected $model = AiToolCall::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'conversation_id' => AiChatConversation::factory(),
            'message_id' => AiChatMessage::factory(),
            'tool_name' => $this->faker->word(),
            'arguments' => null,
            'result' => null,
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'duration_ms' => $this->faker->optional()->numberBetween(50, 5000),
            'error' => null,
            'user_id' => null,
            'tenant_id' => null,
        ];
    }
}
