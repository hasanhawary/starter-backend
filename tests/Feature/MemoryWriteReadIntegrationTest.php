<?php

namespace Tests\Feature;

use AiChat\Memory\MemoryExtractor;
use AiChat\Memory\MemoryRetriever;
use AiChat\Models\AiChatConversation;
use AiChat\Models\AiChatMessage;
use AiChat\Models\AiKnowledgeChunk;
use AiChat\Models\AiMemory;
use AiChat\Vector\EmbeddingGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as m;
use Tests\TestCase;

class MemoryWriteReadIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai-chat.memory.enabled' => true]);
    }

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_raw_messages_stay_in_conversation_messages_not_ai_memories(): void
    {
        AiChatConversation::create([
            'id' => 'conv-int-1',
            'session_id' => 'sess-int-1',
            'user_id' => '1',
            'title' => 'Test',
        ]);

        AiChatMessage::create([
            'id' => 'msg-int-1',
            'conversation_id' => 'conv-int-1',
            'session_id' => 'sess-int-1',
            'agent' => 'project_assistant',
            'user_id' => '1',
            'role' => 'user',
            'content' => 'how many users?',
        ]);

        AiChatMessage::create([
            'id' => 'msg-int-2',
            'conversation_id' => 'conv-int-1',
            'session_id' => 'sess-int-1',
            'agent' => 'project_assistant',
            'user_id' => '1',
            'role' => 'assistant',
            'content' => 'There are 525 users.',
        ]);

        $this->assertDatabaseHas('agent_conversation_messages', [
            'conversation_id' => 'conv-int-1',
            'content' => 'how many users?',
        ]);

        $this->assertDatabaseHas('agent_conversation_messages', [
            'conversation_id' => 'conv-int-1',
            'content' => 'There are 525 users.',
        ]);

        $this->assertDatabaseMissing('ai_memories', [
            'conversation_id' => 'conv-int-1',
        ]);
    }

    public function test_name_declaration_creates_memory(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->once()->andReturn([0.1, 0.2, 0.3]);

        $extractor = new MemoryExtractor($embeddings);

        $memoryData = $extractor->extractFromExchange('conv-mem-1', 'اسمي حسن', 'أهلاً حسن');

        $this->assertNotNull($memoryData);

        $memory = $extractor->store($memoryData);

        $this->assertNotNull($memory);
        $this->assertDatabaseHas('ai_memories', [
            'id' => $memory->id,
            'conversation_id' => 'conv-mem-1',
            'content' => 'User name: حسن',
        ]);

        $this->assertNotNull($memory->embedding);
        $this->assertCount(3, $memory->embedding);
    }

    public function test_memory_has_non_empty_embedding(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->once()->andReturn([0.1, 0.2, 0.3, 0.4, 0.5]);

        $extractor = new MemoryExtractor($embeddings);

        $memoryData = $extractor->extractFromExchange('conv-emb-1', 'اسمي أحمد', 'أهلاً أحمد');

        $memory = $extractor->store($memoryData);

        $this->assertNotNull($memory);

        $saved = AiMemory::find($memory->id);

        $this->assertNotNull($saved->embedding);
        $this->assertIsArray($saved->embedding);
        $this->assertCount(5, $saved->embedding);
        $this->assertSame(0.1, $saved->embedding[0]);
    }

    public function test_preference_creates_memory(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->once()->andReturn([0.1, 0.2, 0.3]);

        $extractor = new MemoryExtractor($embeddings);

        $memoryData = $extractor->extractFromExchange(
            'conv-mem-2',
            'Remember that my favorite dashboard color is emerald green',
            'Noted! Your preferred dashboard color is emerald green.',
        );

        $this->assertNotNull($memoryData);

        $memory = $extractor->store($memoryData);

        $this->assertNotNull($memory);
        $this->assertDatabaseHas('ai_memories', [
            'id' => $memory->id,
            'conversation_id' => 'conv-mem-2',
        ]);

        $this->assertNotNull($memory->embedding);
    }

    public function test_memory_retriever_searches_ai_memories_not_knowledge_chunks(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.1, 0.2, 0.3]);

        $retriever = new MemoryRetriever($embeddings);

        AiMemory::create([
            'id' => 'mem-rt-1',
            'conversation_id' => 'conv-rt-1',
            'content' => 'User name: Hassan',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        AiKnowledgeChunk::factory()->create([
            'id' => 'chunk-int-1',
            'content' => 'Refund policy: 30 days',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $results = $retriever->retrieveByConversation('conv-rt-1', 'user name', 5);

        $this->assertNotEmpty($results);
        $this->assertSame('User name: Hassan', $results[0]['content']);

        $chunkCount = AiKnowledgeChunk::whereNotNull('embedding')->count();
        $this->assertSame(1, $chunkCount);

        $ragChunk = AiKnowledgeChunk::first();
        $this->assertStringContainsString('Refund policy', $ragChunk->content);
    }

    public function test_greeting_does_not_create_memory(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $extractor = new MemoryExtractor($embeddings);

        $result = $extractor->extractFromExchange('conv-gr-1', 'ازيك', 'أهلاً!');

        $this->assertNull($result);

        $result2 = $extractor->extractFromExchange('conv-gr-2', 'شكرا', 'عفواً');

        $this->assertNull($result2);
    }

    public function test_tool_query_does_not_create_memory(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->never();

        $extractor = new MemoryExtractor($embeddings);

        $result = $extractor->extractFromExchange(
            'conv-tool-1',
            'how many users?',
            'There are 525 users.',
        );

        $this->assertNull($result);
    }

    public function test_rag_searches_knowledge_chunks_not_ai_memories(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);

        AiMemory::create([
            'id' => 'mem-rg-1',
            'conversation_id' => 'conv-rg-1',
            'content' => 'User name: Hassan',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        AiKnowledgeChunk::factory()->create([
            'id' => 'chunk-rg-1',
            'content' => 'Refund policy: Full refund within 30 days of purchase.',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $chunks = AiKnowledgeChunk::whereNotNull('embedding')
            ->where('content', 'LIKE', '%refund%')
            ->get();

        $this->assertCount(1, $chunks);
        $this->assertStringContainsString('Refund policy', $chunks[0]->content);

        $memory = AiMemory::where('content', 'LIKE', '%Hassan%')->first();
        $this->assertNotNull($memory);
        $this->assertSame('conv-rg-1', $memory->conversation_id);
    }

    public function test_memory_isolation_between_conversations(): void
    {
        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->andReturn([0.1, 0.2, 0.3]);

        $retriever = new MemoryRetriever($embeddings);

        AiMemory::create([
            'id' => 'mem-iso-1',
            'conversation_id' => 'conv-iso-1',
            'content' => 'User name: Alice',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        AiMemory::create([
            'id' => 'mem-iso-2',
            'conversation_id' => 'conv-iso-2',
            'content' => 'User name: Bob',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $aliceMemories = $retriever->retrieveByConversation('conv-iso-1', 'user name', 5);
        $bobMemories = $retriever->retrieveByConversation('conv-iso-2', 'user name', 5);

        $this->assertCount(1, $aliceMemories);
        $this->assertCount(1, $bobMemories);
        $this->assertSame('User name: Alice', $aliceMemories[0]['content']);
        $this->assertSame('User name: Bob', $bobMemories[0]['content']);
    }

    public function test_table_counts_after_memory_operations(): void
    {
        AiChatConversation::create([
            'id' => 'conv-count-1',
            'session_id' => 'sess-count-1',
            'user_id' => '1',
            'title' => 'Memory Test',
        ]);

        $initialMessageCount = AiChatMessage::count();
        $initialMemoryCount = AiMemory::count();

        AiChatMessage::create([
            'id' => 'msg-count-1',
            'conversation_id' => 'conv-count-1',
            'session_id' => 'sess-count-1',
            'agent' => 'project_assistant',
            'user_id' => '1',
            'role' => 'user',
            'content' => 'اسمي حسن',
        ]);

        AiChatMessage::create([
            'id' => 'msg-count-2',
            'conversation_id' => 'conv-count-1',
            'session_id' => 'sess-count-1',
            'agent' => 'project_assistant',
            'user_id' => '1',
            'role' => 'assistant',
            'content' => 'أهلاً حسن',
        ]);

        $afterMessageCount = AiChatMessage::count();
        $this->assertSame($initialMessageCount + 2, $afterMessageCount);

        $embeddings = m::mock(EmbeddingGenerator::class);
        $embeddings->shouldReceive('generate')->once()->andReturn([0.1, 0.2, 0.3]);

        $extractor = new MemoryExtractor($embeddings);

        $memoryData = $extractor->extractFromExchange('conv-count-1', 'اسمي حسن', 'أهلاً حسن');
        $this->assertNotNull($memoryData);
        $extractor->store($memoryData);

        $afterMemoryCount = AiMemory::count();
        $this->assertSame($initialMemoryCount + 1, $afterMemoryCount);

        $finalMessageCount = AiChatMessage::count();
        $this->assertSame($afterMessageCount, $finalMessageCount);
    }
}
