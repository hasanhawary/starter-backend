<?php

namespace Tests\Feature\AiChat\Commands;

use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class IndexKnowledgeCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $knowledgePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->knowledgePath = storage_path('test-knowledge');
        File::ensureDirectoryExists($this->knowledgePath);

        // Mock embeddings so we don't need a real API key
        $this->mock(EmbeddingGenerator::class)
            ->shouldReceive('generate')
            ->andReturn(array_fill(0, 1536, 0.1));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->knowledgePath);
        parent::tearDown();
    }

    public function test_fails_when_path_does_not_exist(): void
    {
        $this->artisan('ai-chat:index-knowledge', ['--path' => 'non/existent/path'])
            ->assertFailed()
            ->expectsOutputToContain('does not exist');
    }

    public function test_warns_when_no_documents_found(): void
    {
        // Empty directory — no supported files
        $this->artisan('ai-chat:index-knowledge', ['--path' => 'storage/test-knowledge'])
            ->assertSuccessful()
            ->expectsOutputToContain('No knowledge documents found');
    }

    public function test_indexes_markdown_files(): void
    {
        File::put($this->knowledgePath.'/overview.md', '# Project Overview\n\nThis is a test document.');

        $this->artisan('ai-chat:index-knowledge', ['--path' => 'storage/test-knowledge'])
            ->assertSuccessful();

        $this->assertDatabaseHas('ai_knowledge_documents', [
            'title' => 'overview',
            'source_type' => 'md',
        ]);
    }

    public function test_indexes_txt_files(): void
    {
        File::put($this->knowledgePath.'/notes.txt', 'Some plain text knowledge content.');

        $this->artisan('ai-chat:index-knowledge', ['--path' => 'storage/test-knowledge'])
            ->assertSuccessful();

        $this->assertDatabaseHas('ai_knowledge_documents', [
            'title' => 'notes',
            'source_type' => 'txt',
        ]);
    }

    public function test_skips_unchanged_documents(): void
    {
        File::put($this->knowledgePath.'/stable.md', '# Stable content');

        // Index once
        $this->artisan('ai-chat:index-knowledge', ['--path' => 'storage/test-knowledge'])
            ->assertSuccessful();

        // Index again — same content hash, should skip
        $this->artisan('ai-chat:index-knowledge', ['--path' => 'storage/test-knowledge'])
            ->assertSuccessful()
            ->expectsOutputToContain('Unchanged');
    }

    public function test_reindex_option_removes_stale_documents(): void
    {
        File::put($this->knowledgePath.'/doc.md', '# Doc');

        $this->artisan('ai-chat:index-knowledge', ['--path' => 'storage/test-knowledge'])
            ->assertSuccessful();

        $this->assertDatabaseCount('ai_knowledge_documents', 1);

        // Delete the file and reindex — stale doc should be removed
        File::delete($this->knowledgePath.'/doc.md');

        $this->artisan('ai-chat:index-knowledge', [
            '--path' => 'storage/test-knowledge',
            '--reindex' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('ai_knowledge_documents', 0);
    }

    public function test_outputs_indexed_and_skipped_counts(): void
    {
        File::put($this->knowledgePath.'/a.md', '# Doc A');
        File::put($this->knowledgePath.'/b.md', '# Doc B');

        $this->artisan('ai-chat:index-knowledge', ['--path' => 'storage/test-knowledge'])
            ->assertSuccessful()
            ->expectsOutputToContain('Indexed: 2');
    }

    public function test_ignores_unsupported_file_extensions(): void
    {
        File::put($this->knowledgePath.'/script.php', '<?php echo "hello";');
        File::put($this->knowledgePath.'/image.png', 'fake-image-data');
        File::put($this->knowledgePath.'/valid.md', '# Valid');

        $this->artisan('ai-chat:index-knowledge', ['--path' => 'storage/test-knowledge'])
            ->assertSuccessful();

        $this->assertDatabaseCount('ai_knowledge_documents', 1);
    }
}
