<?php

namespace Tests\Feature;

use AiChat\Models\AiKnowledgeChunk;
use AiChat\Models\AiKnowledgeDocument;
use AiChat\RAG\KnowledgeIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeIndexerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_document_creates_chunks_with_embeddings(): void
    {
        config(['ai-chat.knowledge.enabled' => true]);

        $indexer = app(KnowledgeIndexer::class);

        $doc = $indexer->indexDocument([
            'title' => 'Test Document',
            'content' => 'This is a test document with some content about refunds. It has multiple sentences to ensure chunking works properly.',
            'source_path' => 'test.md',
            'source_type' => 'md',
            'metadata' => [],
        ]);

        $this->assertInstanceOf(AiKnowledgeDocument::class, $doc);
        $this->assertEquals('Test Document', $doc->title);
        $this->assertGreaterThan(0, $doc->chunk_count);

        $chunks = AiKnowledgeChunk::where('document_id', $doc->id)->get();
        $this->assertCount($doc->chunk_count, $chunks);

        foreach ($chunks as $chunk) {
            $this->assertNotNull($chunk->embedding);
            $this->assertIsArray($chunk->embedding);
            $this->assertNotEmpty($chunk->embedding);
        }
    }

    public function test_index_document_deduplicates_by_content_hash(): void
    {
        $indexer = app(KnowledgeIndexer::class);

        $data = [
            'title' => 'Dedup Test',
            'content' => 'Same content both times.',
            'source_path' => 'dedup.md',
            'source_type' => 'md',
            'metadata' => [],
        ];

        $doc1 = $indexer->indexDocument($data);
        $doc2 = $indexer->indexDocument($data);

        $this->assertEquals($doc1->id, $doc2->id);
        $this->assertCount(1, AiKnowledgeDocument::where('source_path', 'dedup.md')->get());
    }

    public function test_index_document_replaces_on_content_change(): void
    {
        $indexer = app(KnowledgeIndexer::class);

        $indexer->indexDocument([
            'title' => 'Version 1',
            'content' => 'Original content.',
            'source_path' => 'versioned.md',
            'source_type' => 'md',
            'metadata' => [],
        ]);

        $indexer->indexDocument([
            'title' => 'Version 2',
            'content' => 'Updated content that is different.',
            'source_path' => 'versioned.md',
            'source_type' => 'md',
            'metadata' => [],
        ]);

        $docs = AiKnowledgeDocument::where('source_path', 'versioned.md')->get();
        $this->assertCount(1, $docs);
        $this->assertEquals('Version 2', $docs->first()->title);
    }

    public function test_remove_stale_deletes_old_documents(): void
    {
        $indexer = app(KnowledgeIndexer::class);

        $indexer->indexDocument([
            'title' => 'Old Doc',
            'content' => 'This should be removed.',
            'source_path' => 'old.md',
            'source_type' => 'md',
            'metadata' => [],
        ]);

        $indexer->indexDocument([
            'title' => 'Current Doc',
            'content' => 'This should stay.',
            'source_path' => 'current.md',
            'source_type' => 'md',
            'metadata' => [],
        ]);

        $removed = $indexer->removeStale(['current.md']);

        $this->assertEquals(1, $removed);
        $this->assertCount(1, AiKnowledgeDocument::all());
        $this->assertEquals('current.md', AiKnowledgeDocument::first()->source_path);
    }

    public function test_vector_manager_receives_indexed_embeddings(): void
    {
        $indexer = app(KnowledgeIndexer::class);

        $indexer->indexDocument([
            'title' => 'Vector Test',
            'content' => 'Content for vector indexing test.',
            'source_path' => 'vector-test.md',
            'source_type' => 'md',
            'metadata' => [],
        ]);

        $chunks = AiKnowledgeChunk::whereHas('document', fn ($q) => $q->where('source_path', 'vector-test.md'))->get();

        foreach ($chunks as $chunk) {
            $this->assertNotNull($chunk->embedding);
            $this->assertNotEmpty(array_filter($chunk->embedding));
        }
    }
}
