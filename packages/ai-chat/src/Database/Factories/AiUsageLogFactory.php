<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiUsageLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiUsageLogFactory extends Factory
{
    protected $model = AiUsageLog::class;

    public function definition(): array
    {
        $inputTokens = $this->faker->numberBetween(10, 2000);
        $outputTokens = $this->faker->numberBetween(10, 1000);

        return [
            'id' => (string) Str::uuid7(),
            'user_id' => null,
            'conversation_id' => null,
            'provider' => $this->faker->randomElement(['glm', 'openai', 'anthropic']),
            'model' => $this->faker->randomElement(['glm-4', 'gpt-4o', 'claude-3-5-sonnet']),
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $inputTokens + $outputTokens,
            'cost' => $this->faker->randomFloat(6, 0, 0.05),
            'latency_ms' => $this->faker->numberBetween(100, 5000),
            'status' => 'success',
            'error' => null,
        ];
    }
}
