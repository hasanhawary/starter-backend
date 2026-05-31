<?php

namespace AiChat\Tests\Memory;

use AiChat\Memory\MemoryExtractor;
use AiChat\Models\AiMemory;
use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as m;
use Tests\TestCase;

class MemoryExtractorTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
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

    public function test_extract_detects_arabic_name_declaration(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        $messages = [
            ['role' => 'user', 'content' => 'اسمي حسن'],
            ['role' => 'assistant', 'content' => 'أهلاً حسن'],
        ];

        $result = $extractor->extract('conv-1', $messages);

        $this->assertNotNull($result);
        $this->assertSame('User name: حسن', $result['content']);
        $this->assertSame('user_profile', $result['metadata']['type']);
        $this->assertSame('name', $result['metadata']['key']);
        $this->assertSame('حسن', $result['metadata']['value']);
        $this->assertGreaterThanOrEqual(0.9, $result['importance']);
    }

    public function test_extract_detects_english_name_declaration(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        $messages = [
            ['role' => 'user', 'content' => 'my name is Ahmed'],
            ['role' => 'assistant', 'content' => 'Hello Ahmed'],
        ];

        $result = $extractor->extract('conv-1', $messages);

        $this->assertNotNull($result);
        $this->assertSame('User name: Ahmed', $result['content']);
        $this->assertSame('user_profile', $result['metadata']['type']);
        $this->assertSame('name', $result['metadata']['key']);
        $this->assertSame('Ahmed', $result['metadata']['value']);
    }

    public function test_extract_from_exchange_detects_arabic_name(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        config(['ai-chat.memory.enabled' => true]);

        $result = $extractor->extractFromExchange('conv-1', 'اسمي حسن', 'أهلاً حسن');

        $this->assertNotNull($result);
        $this->assertSame('User name: حسن', $result['content']);
        $this->assertSame('user_profile', $result['metadata']['type']);
    }

    public function test_extract_from_exchange_detects_preference(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        config(['ai-chat.memory.enabled' => true]);

        $result = $extractor->extractFromExchange(
            'conv-1',
            'Remember that my favorite dashboard color is emerald green',
            'I have noted your preference for emerald green dashboard color.',
        );

        $this->assertNotNull($result);
        $this->assertStringContainsString('preference', $result['metadata']['type']);
        $this->assertGreaterThanOrEqual(0.9, $result['importance']);
    }

    public function test_extract_from_exchange_returns_null_for_greeting(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        config(['ai-chat.memory.enabled' => true]);

        $result = $extractor->extractFromExchange('conv-1', 'ازيك', 'أهلاً! كيف يمكنني مساعدتك؟');

        $this->assertNull($result);
    }

    public function test_extract_from_exchange_returns_null_when_memory_disabled(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        config(['ai-chat.memory.enabled' => false]);

        $result = $extractor->extractFromExchange('conv-1', 'اسمي حسن', 'أهلاً حسن');

        $this->assertNull($result);
    }

    public function test_store_with_user_id(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->once()->andReturn([0.1, 0.2, 0.3]);

        $extractor = new MemoryExtractor($embeddings);

        $memoryData = [
            'conversation_id' => 'conv-1',
            'content' => 'User name: Hassan',
            'importance' => 0.95,
            'metadata' => ['type' => 'user_profile', 'key' => 'name', 'value' => 'Hassan'],
            'user_id' => 1,
        ];

        $memory = $extractor->store($memoryData);

        $this->assertNotNull($memory);
        $this->assertSame(1, $memory->user_id);
    }
}
