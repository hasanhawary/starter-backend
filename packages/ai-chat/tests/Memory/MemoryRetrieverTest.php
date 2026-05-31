<?php

namespace AiChat\Tests\Memory;

use AiChat\Memory\MemoryRetriever;
use AiChat\Models\AiKnowledgeChunk;
use AiChat\Models\AiMemory;
use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as m;
use Tests\TestCase;

class MemoryRetrieverTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_retrieve_searches_ai_memories_not_ai_knowledge_chunks(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.1, 0.2, 0.3]);

        $retriever = new MemoryRetriever($embeddings);

        AiMemory::create([
            'id' => 'mem-1',
            'conversation_id' => 'conv-1',
            'content' => 'User name: Hassan',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        AiKnowledgeChunk::factory()->create([
            'id' => 'chunk-1',
            'content' => 'This is a knowledge chunk, not memory',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $retriever->retrieve('Hassan', 'conv-1', 5);

        $this->assertNotEmpty($results);
        $this->assertSame('User name: Hassan', $results[0]['content']);
    }

    public function test_retrieve_does_not_return_knowledge_chunks(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.1, 0.2, 0.3]);

        $retriever = new MemoryRetriever($embeddings);

        AiKnowledgeChunk::factory()->create([
            'id' => 'chunk-2',
            'content' => 'Refund policy knowledge',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $retriever->retrieve('refund policy', 'conv-1', 5);

        $this->assertEmpty($results);
    }

    public function test_retrieve_by_conversation_scopes_correctly(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.1, 0.2, 0.3]);

        $retriever = new MemoryRetriever($embeddings);

        AiMemory::create([
            'id' => 'mem-2',
            'conversation_id' => 'conv-1',
            'content' => 'User name: Ahmed',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        AiMemory::create([
            'id' => 'mem-2b',
            'conversation_id' => 'conv-2',
            'content' => 'User name: Mohamed',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $retriever->retrieveByConversation('conv-1', 'user name', 5);

        $this->assertCount(1, $results);
        $this->assertSame('User name: Ahmed', $results[0]['content']);
    }

    public function test_retrieve_falls_back_to_text_search(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.9, 0.9, 0.9]);

        $retriever = new MemoryRetriever($embeddings);

        AiMemory::create([
            'id' => 'mem-3',
            'conversation_id' => 'conv-1',
            'content' => 'User preference: dashboard color is emerald green',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $retriever->retrieve('emerald green', 'conv-1', 5);

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('emerald green', $results[0]['content']);
    }

    public function test_text_search_uses_keyword_matching(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.9, 0.9, 0.9]);

        $retriever = new MemoryRetriever($embeddings);

        AiMemory::create([
            'id' => 'mem-4',
            'conversation_id' => 'conv-1',
            'content' => 'User name: Hassan',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $retriever->retrieve('Hassan', 'conv-1', 5);

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('Hassan', $results[0]['content']);
    }

    public function test_retrieve_returns_empty_for_no_conversation_match(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.1, 0.2, 0.3]);

        $retriever = new MemoryRetriever($embeddings);

        AiMemory::create([
            'id' => 'mem-5',
            'conversation_id' => 'conv-1',
            'content' => 'User name: Ali',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $retriever->retrieve('user name', 'conv-9', 5);

        $this->assertEmpty($results);
    }

    public function test_does_not_search_ai_knowledge_chunks(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.1, 0.2, 0.3]);

        $retriever = new MemoryRetriever($embeddings);

        AiMemory::create([
            'id' => 'mem-6',
            'conversation_id' => 'conv-1',
            'content' => 'User name: Hassan',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        AiKnowledgeChunk::factory()->create([
            'id' => 'chunk-3',
            'content' => 'Secret project knowledge',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $retriever->retrieve('user name', ['conversation_id' => 'conv-1'], 5);

        $this->assertNotEmpty($results);
        $this->assertSame('User name: Hassan', $results[0]['content']);
    }
}
