<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiChatConversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiChatConversationFactory extends Factory
{
    protected $model = AiChatConversation::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'session_id' => 'sess_'.Str::uuid(),
            'ip_address' => $this->faker->ipv4(),
            'title' => $this->faker->sentence(3),
            'metadata' => null,
            'last_message_at' => now(),
        ];
    }
}
