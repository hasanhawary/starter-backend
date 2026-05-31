<?php

namespace AiChat\Tests\Feature;

use AiChat\Models\AiChatConversation;
use AiChat\Models\AiChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationHistoryResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai-chat.rate_limiting.enabled' => false]);
    }

    public function test_get_conversation_returns_messages_in_the_api_response(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => 'session-12345',
            'title' => 'History check',
        ]);

        AiChatMessage::factory()->user()->create([
            'conversation_id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'content' => 'Hello',
        ]);

        AiChatMessage::factory()->assistant()->create([
            'conversation_id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'content' => 'Hi there',
        ]);

        $response = $this->getJson('/api/ai-chat/conversations/'.$conversation->id.'?session_id='.$conversation->session_id);

        $response->assertOk();
        $response->assertJsonPath('data.conversation.id', $conversation->id);
        $response->assertJsonPath('data.conversation.message_count', 2);
        $response->assertJsonCount(2, 'data.messages');
        $response->assertJsonFragment(['content' => 'Hello']);
        $response->assertJsonFragment(['content' => 'Hi there']);
    }
}
