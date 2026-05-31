<?php

namespace AiChat\Tests\Memory;

use AiChat\Memory\Stores\DatabaseMemoryStore;
use AiChat\Models\AiMemory;
use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as m;
use Tests\TestCase;

class DatabaseMemoryStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_store_creates_memory_row(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $store = new DatabaseMemoryStore($embeddings);

        $result = $store->store('conv-1', 'User name: Ahmed', [0.1, 0.2, 0.3]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('ai_memories', [
            'conversation_id' => 'conv-1',
            'content' => 'User name: Ahmed',
        ]);
    }

    public function test_retrieve_returns_scored_memories(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $store = new DatabaseMemoryStore($embeddings);

        AiMemory::create([
            'id' => 'mem-1',
            'conversation_id' => 'conv-1',
            'content' => 'User name: Ahmed',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        AiMemory::create([
            'id' => 'mem-2',
            'conversation_id' => 'conv-1',
            'content' => 'User preference: color = blue',
            'embedding' => [0.0, 1.0, 0.0],
        ]);

        $results = $store->retrieve('conv-1', [0.1, 0.2, 0.3], 5);

        $this->assertNotEmpty($results);
        $this->assertCount(1, $results);
        $this->assertSame('User name: Ahmed', $results[0]['content']);
        $this->assertGreaterThanOrEqual(0.99, $results[0]['score']);
    }

    public function test_retrieve_scoped_by_conversation(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $store = new DatabaseMemoryStore($embeddings);

        AiMemory::create([
            'id' => 'mem-3',
            'conversation_id' => 'conv-1',
            'content' => 'Conv 1 memory',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        AiMemory::create([
            'id' => 'mem-4',
            'conversation_id' => 'conv-2',
            'content' => 'Conv 2 memory',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $store->retrieve('conv-1', [0.1, 0.2, 0.3], 5);

        $this->assertCount(1, $results);
        $this->assertSame('Conv 1 memory', $results[0]['content']);
    }

    public function test_retrieve_returns_empty_for_no_match(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $store = new DatabaseMemoryStore($embeddings);

        AiMemory::create([
            'id' => 'mem-5',
            'conversation_id' => 'conv-1',
            'content' => 'Test memory',
            'embedding' => [0.0, 1.0, 0.0],
        ]);

        $results = $store->retrieve('conv-1', [1.0, 0.0, 0.0], 5);

        $this->assertEmpty($results);
    }

    public function test_forget_deletes_memories(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $store = new DatabaseMemoryStore($embeddings);

        AiMemory::create([
            'id' => 'mem-6',
            'conversation_id' => 'conv-1',
            'content' => 'Test memory',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $result = $store->forget('conv-1');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('ai_memories', [
            'conversation_id' => 'conv-1',
        ]);
    }

    public function test_cosine_similarity_exact_match(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $store = new DatabaseMemoryStore($embeddings);

        $ref = new \ReflectionMethod($store, 'cosineSimilarity');

        $score = $ref->invoke($store, [0.1, 0.2, 0.3], [0.1, 0.2, 0.3]);

        $this->assertEqualsWithDelta(1.0, $score, 0.001);
    }

    public function test_cosine_similarity_no_match(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $store = new DatabaseMemoryStore($embeddings);

        $ref = new \ReflectionMethod($store, 'cosineSimilarity');

        $score = $ref->invoke($store, [1.0, 0.0, 0.0], [0.0, 1.0, 0.0]);

        $this->assertEqualsWithDelta(0.0, $score, 0.001);
    }
}
