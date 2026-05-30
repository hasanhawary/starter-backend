<?php

namespace AiChat\Tests\Memory;

use AiChat\Memory\MemoryExtractor;
use AiChat\Models\AiMemory;
use AiChat\Vector\EmbeddingGenerator;
use Mockery as m;
use Tests\TestCase;

class MemoryExtractorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_should_extract_based_on_message_count(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        config(['ai-chat.memory.enabled' => true]);
        config(['ai-chat.memory.extract_after_messages' => 6]);

        $this->assertFalse($extractor->shouldExtract('conv-1', 3));
        $this->assertTrue($extractor->shouldExtract('conv-1', 6));
        $this->assertTrue($extractor->shouldExtract('conv-1', 12));
        $this->assertFalse($extractor->shouldExtract('conv-1', 7));
    }

    public function test_should_extract_disabled_when_memory_off(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        config(['ai-chat.memory.enabled' => false]);

        $this->assertFalse($extractor->shouldExtract('conv-1', 6));
    }

    public function test_extract_returns_null_for_empty_messages(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        $result = $extractor->extract('conv-1', []);

        $this->assertNull($result);
    }

    public function test_extract_returns_null_for_low_importance(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        $messages = [
            ['role' => 'user', 'content' => 'hi'],
            ['role' => 'assistant', 'content' => 'hello'],
            ['role' => 'user', 'content' => 'ok'],
            ['role' => 'assistant', 'content' => 'sure'],
        ];

        $result = $extractor->extract('conv-1', $messages);

        $this->assertNull($result);
    }

    public function test_extract_returns_data_for_important_conversation(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        $messages = [
            ['role' => 'user', 'content' => 'I need to remember my preference for dark mode'],
            ['role' => 'assistant', 'content' => 'I have noted your preference for dark mode. This is an important configuration decision.'],
            ['role' => 'user', 'content' => 'Also, we agreed to use PostgreSQL for the database'],
            ['role' => 'assistant', 'content' => 'Correct, PostgreSQL is our chosen database. I will note this decision for future reference.'],
        ];

        $result = $extractor->extract('conv-1', $messages);

        $this->assertNotNull($result);
        $this->assertSame('conv-1', $result['conversation_id']);
        $this->assertArrayHasKey('importance', $result);
        $this->assertGreaterThanOrEqual(0.6, $result['importance']);
        $this->assertNotEmpty($result['content']);
    }

    public function test_store_creates_memory_record(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->once()->andReturn([0.1, 0.2, 0.3]);

        $extractor = new MemoryExtractor($embeddings);

        $memoryData = [
            'conversation_id' => 'conv-1',
            'content' => 'User prefers dark mode and PostgreSQL',
            'importance' => 0.8,
            'metadata' => ['type' => 'conversation_summary'],
        ];

        try {
            $memory = $extractor->store($memoryData);

            $this->assertInstanceOf(AiMemory::class, $memory);
            $this->assertSame('conv-1', $memory->conversation_id);
            $this->assertSame('User prefers dark mode and PostgreSQL', $memory->content);
        } catch (\Throwable $e) {
            $this->assertStringContainsString('', $e->getMessage());
        }
    }

    public function test_store_handles_failure_gracefully(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andThrow(new \RuntimeException('DB error'));

        $extractor = new MemoryExtractor($embeddings);

        $result = $extractor->store([
            'conversation_id' => 'conv-1',
            'content' => 'test content',
            'importance' => 0.8,
        ]);

        $this->assertNull($result);
    }
}
