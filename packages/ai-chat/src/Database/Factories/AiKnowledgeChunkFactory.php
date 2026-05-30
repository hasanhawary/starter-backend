<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiKnowledgeChunk;
use AiChat\Models\AiKnowledgeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiKnowledgeChunkFactory extends Factory
{
    protected $model = AiKnowledgeChunk::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'document_id' => AiKnowledgeDocument::factory(),
            'content' => $this->faker->paragraph(),
            'chunk_index' => $this->faker->numberBetween(0, 100),
            'embedding' => null,
            'metadata' => null,
        ];
    }
}
