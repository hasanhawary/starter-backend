<?php

namespace Tests\Feature\AiChat;

use HasanHawary\AiChat\Agents\ChatAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_session_id_returns_validation_error(): void
    {
        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['session_id']);
    }

    public function test_missing_message_returns_validation_error(): void
    {
        $response = $this->postJson('/api/ai-chat/messages', [
            'session_id' => 'test-session-1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_list_conversations_requires_session_id(): void
    {
        $response = $this->getJson('/api/ai-chat/conversations');

        $response->assertStatus(422);
    }

    public function test_list_conversations_returns_empty_for_new_session(): void
    {
        $response = $this->getJson('/api/ai-chat/conversations?session_id=new-test-session-12345');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function test_non_existent_conversation_returns_404(): void
    {
        $response = $this->getJson('/api/ai-chat/conversations/non-existent-id?session_id=test-session-1234567890');

        $response->assertStatus(404);
    }

    public function test_chat_agent_can_be_initialized(): void
    {
        $agent = new ChatAgent;

        $this->assertInstanceOf(ChatAgent::class, $agent);
        $this->assertNotNull($agent->instructions());
    }

    public function test_chat_agent_custom_system_prompt(): void
    {
        $agent = new ChatAgent('Custom instructions');

        $this->assertEquals('Custom instructions', (string) $agent->instructions());
    }

    public function test_chat_agent_session_binding(): void
    {
        $agent = new ChatAgent;
        $agent->forSession('test-session-abc');

        $this->assertEquals('test-session-abc', $agent->sessionId());
        $this->assertNull($agent->currentConversation());
    }

    public function test_send_message_with_faked_agent(): void
    {
        ChatAgent::fake([
            'This is a test response.',
        ]);

        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
            'session_id' => 'test-session-fake-123',
            'stream' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['message', 'conversation_id']]);
    }

    public function test_delete_non_existent_conversation_returns_404(): void
    {
        $response = $this->deleteJson('/api/ai-chat/conversations/non-existent-id?session_id=test-session-1234567890');

        $response->assertStatus(404);
    }

    public function test_list_conversations_response_format(): void
    {
        $response = $this->getJson('/api/ai-chat/conversations?session_id=test-session-1234567890');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data',
            ]);

        $this->assertTrue($response->json('status'));
        $this->assertEquals(200, $response->json('code'));
    }

    public function test_get_conversation_response_format(): void
    {
        $response = $this->getJson('/api/ai-chat/conversations/non-existent-id?session_id=test-session-1234567890');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertFalse($response->json('status'));
        $this->assertEquals(404, $response->json('code'));
    }

    public function test_validation_error_response_format(): void
    {
        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }
}
