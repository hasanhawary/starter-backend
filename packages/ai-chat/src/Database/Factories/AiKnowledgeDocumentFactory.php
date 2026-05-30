<?php

namespace AiChat\Database\Factories;

use AiChat\Models\AiKnowledgeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiKnowledgeDocumentFactory extends Factory
{
    protected $model = AiKnowledgeDocument::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'title' => $this->faker->sentence(3),
            'source_path' => $this->faker->filePath(),
            'source_type' => $this->faker->randomElement(['markdown', 'readme', 'docs', 'project_map']),
            'content_hash' => hash('sha256', $this->faker->text()),
            'chunk_count' => 0,
            'metadata' => null,
            'indexed_at' => null,
        ];
    }
}
