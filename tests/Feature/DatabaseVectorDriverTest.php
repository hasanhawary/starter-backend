<?php

namespace Tests\Feature;

use AiChat\Models\AiKnowledgeChunk;
use AiChat\Models\AiKnowledgeDocument;
use AiChat\Vector\Drivers\DatabaseVectorDriver;
use AiChat\Vector\EmbeddingGenerator;
use AiChat\Vector\VectorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseVectorDriverTest extends TestCase
{
    use RefreshDatabase;

    protected DatabaseVectorDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = new DatabaseVectorDriver;
    }

    public function test_index_stores_embedding_on_chunk(): void
    {
        $doc = AiKnowledgeDocument::create([
            'id' => 'doc-1',
            'title' => 'Test Doc',
            'source_path' => 'test.md',
            'source_type' => 'md',
            'content_hash' => md5('test'),
            'chunk_count' => 1,
            'indexed_at' => now(),
        ]);

        $chunk = AiKnowledgeChunk::create([
            'id' => 'chunk-1',
            'document_id' => $doc->id,
            'content' => 'Test content',
            'chunk_index' => 0,
            'embedding' => null,
        ]);

        $embedding = array_fill(0, 10, 0.5);

        $result = $this->driver->index('chunk-1', $embedding);

        $this->assertTrue($result);
        $chunk->refresh();
        $this->assertNotNull($chunk->embedding);
        $this->assertCount(10, $chunk->embedding);
    }

    public function test_search_finds_similar_embeddings(): void
    {
        $doc = AiKnowledgeDocument::create([
            'id' => 'doc-2',
            'title' => 'Test Doc 2',
            'source_path' => 'test2.md',
            'source_type' => 'md',
            'content_hash' => md5('test2'),
            'chunk_count' => 2,
            'indexed_at' => now(),
        ]);

        $embedding1 = array_fill(0, 8, 1.0);
        $embedding2 = array_fill(0, 8, 0.0);

        AiKnowledgeChunk::create([
            'id' => 'chunk-2a',
            'document_id' => $doc->id,
            'content' => 'Similar content',
            'chunk_index' => 0,
            'embedding' => $embedding1,
        ]);

        AiKnowledgeChunk::create([
            'id' => 'chunk-2b',
            'document_id' => $doc->id,
            'content' => 'Different content',
            'chunk_index' => 1,
            'embedding' => $embedding2,
        ]);

        $queryEmbedding = array_fill(0, 8, 0.9);

        $results = $this->driver->search($queryEmbedding, 5, 0.5);

        $this->assertNotEmpty($results);
        $this->assertEquals('chunk-2a', $results[0]['id']);
        $this->assertGreaterThan(0.5, $results[0]['score']);
    }

    public function test_search_respects_threshold(): void
    {
        $doc = AiKnowledgeDocument::create([
            'id' => 'doc-3',
            'title' => 'Test Doc 3',
            'source_path' => 'test3.md',
            'source_type' => 'md',
            'content_hash' => md5('test3'),
            'chunk_count' => 1,
            'indexed_at' => now(),
        ]);

        $orthogonalEmbedding = [1.0, 0.0, 0.0, 0.0];
        $queryEmbedding = [0.0, 1.0, 0.0, 0.0];

        AiKnowledgeChunk::create([
            'id' => 'chunk-3',
            'document_id' => $doc->id,
            'content' => 'Orthogonal',
            'chunk_index' => 0,
            'embedding' => $orthogonalEmbedding,
        ]);

        $results = $this->driver->search($queryEmbedding, 5, 0.1);

        $this->assertEmpty($results);
    }

    public function test_search_respects_limit(): void
    {
        $doc = AiKnowledgeDocument::create([
            'id' => 'doc-4',
            'title' => 'Test Doc 4',
            'source_path' => 'test4.md',
            'source_type' => 'md',
            'content_hash' => md5('test4'),
            'chunk_count' => 3,
            'indexed_at' => now(),
        ]);

        for ($i = 0; $i < 3; $i++) {
            AiKnowledgeChunk::create([
                'id' => "chunk-4-{$i}",
                'document_id' => $doc->id,
                'content' => "Content {$i}",
                'chunk_index' => $i,
                'embedding' => array_fill(0, 4, 1.0),
            ]);
        }

        $queryEmbedding = array_fill(0, 4, 1.0);

        $results = $this->driver->search($queryEmbedding, 2, 0.5);

        $this->assertCount(2, $results);
    }

    public function test_delete_removes_embedding(): void
    {
        $doc = AiKnowledgeDocument::create([
            'id' => 'doc-5',
            'title' => 'Test Doc 5',
            'source_path' => 'test5.md',
            'source_type' => 'md',
            'content_hash' => md5('test5'),
            'chunk_count' => 1,
            'indexed_at' => now(),
        ]);

        AiKnowledgeChunk::create([
            'id' => 'chunk-5',
            'document_id' => $doc->id,
            'content' => 'To delete',
            'chunk_index' => 0,
            'embedding' => array_fill(0, 4, 1.0),
        ]);

        $result = $this->driver->delete('chunk-5');

        $this->assertTrue($result);
        $chunk = AiKnowledgeChunk::find('chunk-5');
        $this->assertNull($chunk->embedding);
    }

    public function test_delete_all_clears_embeddings(): void
    {
        $doc = AiKnowledgeDocument::create([
            'id' => 'doc-6',
            'title' => 'Test Doc 6',
            'source_path' => 'test6.md',
            'source_type' => 'md',
            'content_hash' => md5('test6'),
            'chunk_count' => 2,
            'indexed_at' => now(),
        ]);

        for ($i = 0; $i < 2; $i++) {
            AiKnowledgeChunk::create([
                'id' => "chunk-6-{$i}",
                'document_id' => $doc->id,
                'content' => "Content {$i}",
                'chunk_index' => $i,
                'embedding' => array_fill(0, 4, 1.0),
            ]);
        }

        $result = $this->driver->deleteAll();

        $this->assertTrue($result);

        foreach (AiKnowledgeChunk::where('document_id', $doc->id)->get() as $chunk) {
            $this->assertNull($chunk->embedding);
        }
    }

    public function test_cosine_similarity_perfect_match(): void
    {
        $doc = AiKnowledgeDocument::create([
            'id' => 'doc-7',
            'title' => 'Test Doc 7',
            'source_path' => 'test7.md',
            'source_type' => 'md',
            'content_hash' => md5('test7'),
            'chunk_count' => 1,
            'indexed_at' => now(),
        ]);

        $embedding = [1.0, 0.0, 0.0, 0.0];

        AiKnowledgeChunk::create([
            'id' => 'chunk-7',
            'document_id' => $doc->id,
            'content' => 'Perfect match',
            'chunk_index' => 0,
            'embedding' => $embedding,
        ]);

        $results = $this->driver->search($embedding, 1, 0.99);

        $this->assertCount(1, $results);
        $this->assertEquals(1.0, $results[0]['score']);
    }

    public function test_vector_manager_resolves_database_driver(): void
    {
        config(['ai-chat.vector.driver' => 'database']);

        $manager = new VectorManager;

        $this->assertInstanceOf(DatabaseVectorDriver::class, $manager->getDriver());
    }

    public function test_embedding_generator_produces_consistent_vectors(): void
    {
        $generator = new EmbeddingGenerator;

        $embedding1 = $generator->generate('hello world');
        $embedding2 = $generator->generate('hello world');

        $this->assertIsArray($embedding1);
        $this->assertNotEmpty($embedding1);
        $this->assertEquals($embedding1, $embedding2);
    }
}
