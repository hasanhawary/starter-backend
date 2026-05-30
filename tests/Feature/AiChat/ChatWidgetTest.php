<?php

namespace Tests\Feature\AiChat;

use HasanHawary\AiChat\Agents\ChatAgent;
use HasanHawary\AiChat\Models\AiChatConversation;
use HasanHawary\AiChat\Models\AiChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatWidgetTest extends TestCase
{
    use RefreshDatabase;

    private string $sessionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionId = 'sess_'.Str::uuid()->toString();
    }

    // ─── Send Message (Non-Streaming) ───────────────────────────────

    public function test_send_message_returns_faked_response(): void
    {
        ChatAgent::fake(['Hello! How can I help you?']);

        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hi there',
            'session_id' => $this->sessionId,
            'stream' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => ['message', 'conversation_id'],
            ])
            ->assertJson([
                'status' => true,
                'code' => 200,
            ])
            ->assertJsonPath('data.message', 'Hello! How can I help you?');
    }

    public function test_send_message_continues_existing_conversation(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        ChatAgent::fake(['Follow-up response.']);

        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Follow up',
            'session_id' => $this->sessionId,
            'conversation_id' => $conversation->id,
            'stream' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.conversation_id', $conversation->id);
    }

    public function test_send_message_with_custom_system_prompt(): void
    {
        ChatAgent::fake(['Custom response.']);

        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
            'session_id' => $this->sessionId,
            'system_prompt' => 'You are a coding assistant.',
            'stream' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Custom response.');
    }

    // ─── Send Message (Streaming) ───────────────────────────────────

    public function test_send_message_streaming_returns_event_stream(): void
    {
        ChatAgent::fake(['Streaming response.']);

        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hi',
            'session_id' => $this->sessionId,
            'stream' => true,
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
    }

    // ─── Validation ─────────────────────────────────────────────────

    public function test_missing_session_id_returns_422(): void
    {
        $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['session_id']);
    }

    public function test_missing_message_returns_422(): void
    {
        $this->postJson('/api/ai-chat/messages', [
            'session_id' => $this->sessionId,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_session_id_too_short_returns_422(): void
    {
        $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
            'session_id' => 'short',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['session_id']);
    }

    public function test_message_exceeds_max_length_returns_422(): void
    {
        $this->postJson('/api/ai-chat/messages', [
            'message' => str_repeat('a', 10001),
            'session_id' => $this->sessionId,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_invalid_conversation_id_format_returns_422(): void
    {
        $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
            'session_id' => $this->sessionId,
            'conversation_id' => 'not-a-uuid',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['conversation_id']);
    }

    public function test_validation_error_response_format(): void
    {
        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    // ─── List Conversations ─────────────────────────────────────────

    public function test_list_conversations_requires_session_id(): void
    {
        $this->getJson('/api/ai-chat/conversations')
            ->assertStatus(422);
    }

    public function test_list_conversations_returns_empty_for_new_session(): void
    {
        $response = $this->getJson("/api/ai-chat/conversations?session_id={$this->sessionId}");

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'code', 'message', 'data']);

        $data = $response->json('data');
        $items = $data['data'] ?? $data;
        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    public function test_list_conversations_returns_conversations_for_session(): void
    {
        AiChatConversation::factory()->count(3)->create([
            'session_id' => $this->sessionId,
        ]);

        AiChatConversation::factory()->count(2)->create([
            'session_id' => 'other-session-'.Str::uuid(),
        ]);

        $response = $this->getJson("/api/ai-chat/conversations?session_id={$this->sessionId}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $items = $data['data'] ?? $data;
        $this->assertCount(3, $items);
    }

    public function test_list_conversations_response_format(): void
    {
        $response = $this->getJson("/api/ai-chat/conversations?session_id={$this->sessionId}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'code' => 200,
            ])
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data',
            ]);
    }

    public function test_list_conversations_paginates(): void
    {
        AiChatConversation::factory()->count(15)->create([
            'session_id' => $this->sessionId,
        ]);

        $response = $this->getJson("/api/ai-chat/conversations?session_id={$this->sessionId}&per_page=5");

        $response->assertStatus(200);

        $data = $response->json('data');
        $items = $data['data'] ?? $data;
        $this->assertCount(5, $items);
    }

    public function test_list_conversations_items_have_correct_structure(): void
    {
        AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        $response = $this->getJson("/api/ai-chat/conversations?session_id={$this->sessionId}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $items = $data['data'] ?? $data;
        $this->assertArrayHasKey(0, $items);
        $this->assertArrayHasKeys(['id', 'title', 'created_at', 'updated_at', 'last_message_at'], $items[0]);
    }

    // ─── Get Conversation ───────────────────────────────────────────

    public function test_get_conversation_returns_404_for_non_existent(): void
    {
        $this->getJson("/api/ai-chat/conversations/non-existent-id?session_id={$this->sessionId}")
            ->assertStatus(404);
    }

    public function test_get_conversation_requires_session_id(): void
    {
        $this->getJson('/api/ai-chat/conversations/some-id')
            ->assertStatus(422);
    }

    public function test_get_conversation_returns_conversation_with_messages(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        AiChatMessage::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'session_id' => $this->sessionId,
        ]);

        $response = $this->getJson("/api/ai-chat/conversations/{$conversation->id}?session_id={$this->sessionId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'data' => [
                    'conversation' => ['id', 'title'],
                    'messages' => [
                        '*' => ['id', 'role', 'content', 'created_at'],
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data.messages'));
    }

    public function test_get_conversation_returns_404_for_wrong_session(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => 'owner-session-'.Str::uuid(),
        ]);

        $this->getJson("/api/ai-chat/conversations/{$conversation->id}?session_id={$this->sessionId}")
            ->assertStatus(404);
    }

    public function test_get_conversation_404_response_format(): void
    {
        $this->getJson("/api/ai-chat/conversations/non-existent?session_id={$this->sessionId}")
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'code' => 404,
            ])
            ->assertJsonStructure(['status', 'code', 'message']);
    }

    // ─── Delete Conversation ────────────────────────────────────────

    public function test_delete_conversation_removes_conversation_and_messages(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        AiChatMessage::factory()->count(5)->create([
            'conversation_id' => $conversation->id,
            'session_id' => $this->sessionId,
        ]);

        $this->assertModelExists($conversation);
        $this->assertEquals(5, AiChatMessage::where('conversation_id', $conversation->id)->count());

        $this->deleteJson("/api/ai-chat/conversations/{$conversation->id}?session_id={$this->sessionId}")
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertSoftDeleted($conversation);
        $this->assertEquals(0, AiChatMessage::where('conversation_id', $conversation->id)->count());
    }

    public function test_delete_conversation_returns_404_for_non_existent(): void
    {
        $this->deleteJson("/api/ai-chat/conversations/non-existent?session_id={$this->sessionId}")
            ->assertStatus(404);
    }

    public function test_delete_conversation_returns_404_for_wrong_session(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => 'owner-session-'.Str::uuid(),
        ]);

        $this->deleteJson("/api/ai-chat/conversations/{$conversation->id}?session_id={$this->sessionId}")
            ->assertStatus(404);
    }

    // ─── ChatAgent ──────────────────────────────────────────────────

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
        $agent->forSession($this->sessionId);

        $this->assertEquals($this->sessionId, $agent->sessionId());
        $this->assertNull($agent->currentConversation());
    }

    public function test_chat_agent_continue_binding(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        $agent = new ChatAgent;
        $agent->continue($conversation->id, $this->sessionId);

        $this->assertEquals($this->sessionId, $agent->sessionId());
        $this->assertEquals($conversation->id, $agent->currentConversation());
    }

    // ─── Models ─────────────────────────────────────────────────────

    public function test_conversation_has_many_messages(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        AiChatMessage::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->assertCount(3, $conversation->fresh()->messages);
    }

    public function test_message_belongs_to_conversation(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        $message = AiChatMessage::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->assertEquals($conversation->id, $message->conversation->id);
    }

    public function test_conversation_uses_soft_deletes(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        $conversation->delete();

        $this->assertSoftDeleted($conversation);
    }

    public function test_message_casts_json_fields(): void
    {
        $message = AiChatMessage::factory()->create([
            'tool_calls' => [['id' => 'call_1', 'name' => 'test']],
            'tool_results' => [['id' => 'res_1', 'result' => 'ok']],
            'usage' => ['total_tokens' => 100],
            'meta' => ['model' => 'glm-5.1'],
        ]);

        $message = $message->fresh();

        $this->assertIsArray($message->tool_calls);
        $this->assertIsArray($message->tool_results);
        $this->assertIsArray($message->usage);
        $this->assertIsArray($message->meta);
        $this->assertEquals('call_1', $message->tool_calls[0]['id']);
    }

    public function test_conversation_casts_metadata_and_last_message_at(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
            'metadata' => ['source' => 'widget'],
            'last_message_at' => now(),
        ]);

        $conversation = $conversation->fresh();

        $this->assertIsArray($conversation->metadata);
        $this->assertEquals('widget', $conversation->metadata['source']);
        $this->assertInstanceOf(Carbon::class, $conversation->last_message_at);
    }

    // ─── Widget Page ────────────────────────────────────────────────

    public function test_welcome_page_loads_with_widget_script(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('ai-chat-widget.min.js')
            ->assertSee('AIChatWidget.init');
    }

    // ─── Helpers ────────────────────────────────────────────────────

    private function assertArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }
}
