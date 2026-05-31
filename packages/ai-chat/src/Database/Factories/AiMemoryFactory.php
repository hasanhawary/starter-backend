<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiChatConversation;
use AiChat\Models\AiMemory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiMemoryFactory extends Factory
{
    protected $model = AiMemory::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'conversation_id' => AiChatConversation::factory(),
            'user_id' => null,
            'guest_id' => null,
            'tenant_id' => null,
            'agent_id' => null,
            'content' => $this->faker->paragraph(),
            'embedding' => null,
            'metadata' => null,
        ];
    }
}
