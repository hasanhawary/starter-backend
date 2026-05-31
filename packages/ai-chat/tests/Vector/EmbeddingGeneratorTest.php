<?php

namespace AiChat\Tests\Vector;

use AiChat\Vector\EmbeddingGenerator;
use Laravel\Ai\AiManager;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Providers\OpenAiProvider;
use Tests\TestCase;

class EmbeddingGeneratorTest extends TestCase
{
    public function test_openai_embedding_provider_can_be_resolved(): void
    {
        $provider = app(AiManager::class)->embeddingProvider('openai');

        $this->assertInstanceOf(OpenAiProvider::class, $provider);
    }

    public function test_generate_uses_provider_embeddings_method(): void
    {
        Embeddings::fake([
            [[0.1, 0.2, 0.3]],
        ]);

        config(['ai-chat.default_for_embeddings' => 'openai']);

        $generator = new EmbeddingGenerator;

        $embedding = $generator->generate('Hello world');

        $this->assertSame([0.1, 0.2, 0.3], $embedding);
        Embeddings::assertGenerated(function ($prompt): bool {
            return $prompt->contains('Hello world') && $prompt->dimensions === 1536;
        });
    }
}
