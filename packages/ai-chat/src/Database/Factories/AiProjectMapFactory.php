<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiProjectMap;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiProjectMapFactory extends Factory
{
    protected $model = AiProjectMap::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'version' => 1,
            'map_data' => [],
            'scanned_at' => now(),
        ];
    }
}
