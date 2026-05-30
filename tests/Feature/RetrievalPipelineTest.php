<?php

namespace Tests\Feature;

use AiChat\Models\AiKnowledgeChunk;
use AiChat\Models\AiKnowledgeDocument;
use AiChat\RAG\RetrievalPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetrievalPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai-chat.knowledge.enabled' => true]);
    }

    protected function seedKnowledge(string $content, string $sourcePath = 'test.md', ?array $embedding = null): AiKnowledgeChunk
    {
        $doc = AiKnowledgeDocument::create([
            'id' => fake()->uuid(),
            'title' => 'Test Doc',
            'source_path' => $sourcePath,
            'source_type' => 'md',
            'content_hash' => md5($content),
            'chunk_count' => 1,
            'indexed_at' => now(),
        ]);

        return AiKnowledgeChunk::create([
            'id' => fake()->uuid(),
            'document_id' => $doc->id,
            'content' => $content,
            'chunk_index' => 0,
            'embedding' => $embedding ?? array_fill(0, 10, 0.5),
        ]);
    }

    public function test_retrieve_finds_matching_content_via_text(): void
    {
        $this->seedKnowledge('Refunds are allowed within 14 days from the payment date.', 'refund-policy.md');

        $pipeline = app(RetrievalPipeline::class);
        $results = $pipeline->retrieve('What is the refund policy?', 3);

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('Refund', $results[0]['content']);
        $this->assertEquals('refund-policy.md', $results[0]['source']);
        $this->assertGreaterThan(0, $results[0]['score']);
    }

    public function test_retrieve_returns_empty_for_no_matches(): void
    {
        $this->seedKnowledge('The quick brown fox jumps over the lazy dog.', 'animals.md');

        $pipeline = app(RetrievalPipeline::class);
        $results = $pipeline->retrieve('quantum physics equations', 3);

        $this->assertEmpty($results);
    }

    public function test_retrieve_ranks_better_matches_higher(): void
    {
        $this->seedKnowledge('Refund policy allows returns within 14 days.', 'refund.md');
        $this->seedKnowledge('Shipping takes 3-5 business days.', 'shipping.md');

        $pipeline = app(RetrievalPipeline::class);
        $results = $pipeline->retrieve('refund policy', 3);

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('Refund', $results[0]['content']);
    }

    public function test_retrieve_respects_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->seedKnowledge("Refund rule {$i}: details about refunds and returns.", "rule-{$i}.md");
        }

        $pipeline = app(RetrievalPipeline::class);
        $results = $pipeline->retrieve('refund rules', 2);

        $this->assertCount(2, $results);
    }

    public function test_retrieve_with_vector_and_text_hybrid(): void
    {
        $similarEmbedding = array_fill(0, 10, 0.9);
        $this->seedKnowledge('Refunds within 14 days require admin approval.', 'refund.md', $similarEmbedding);

        $pipeline = app(RetrievalPipeline::class);
        $results = $pipeline->retrieve('refund policy', 3);

        $this->assertNotEmpty($results);
        $this->assertEquals('refund.md', $results[0]['source']);
    }

    public function test_retrieve_includes_type_in_results(): void
    {
        $this->seedKnowledge('Some knowledge content.', 'knowledge.md');

        $pipeline = app(RetrievalPipeline::class);
        $results = $pipeline->retrieve('knowledge content', 3);

        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('type', $results[0]);
        $this->assertContains($results[0]['type'], ['vector', 'text', 'hybrid']);
    }
}
