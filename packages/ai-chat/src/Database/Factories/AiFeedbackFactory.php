<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiChatConversation;
use AiChat\Models\AiChatMessage;
use AiChat\Models\AiFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiFeedbackFactory extends Factory
{
    protected $model = AiFeedback::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'message_id' => AiChatMessage::factory(),
            'conversation_id' => AiChatConversation::factory(),
            'user_id' => null,
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
