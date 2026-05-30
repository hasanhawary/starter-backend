<?php

namespace Tests\Feature\AiChat;

use AiChat\Agents\ChatAgent;
use AiChat\Contracts\LLMProviderInterface;
use AiChat\Models\AiChatConversation;
use AiChat\Models\AiChatMessage;
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

    protected function fakeProvider(string $responseText): void
    {
        $this->app->singleton(LLMProviderInterface::class, fn () => new class($responseText) implements LLMProviderInterface
        {
            public function __construct(private string $text) {}

            public function send(array $messages, array $tools = [], array $options = []): array
            {
                return ['content' => $this->text, 'usage' => ['total_tokens' => 50]];
            }

            public function stream(array $messages, array $tools = [], array $options = []): \Generator
            {
                yield ['content' => $this->text];
            }

            public function name(): string
            {
                return 'fake';
            }
        });
    }

    // ─── Send Message (Non-Streaming) ───────────────────────────────

    public function test_send_message_returns_faked_response(): void
    {
        $this->fakeProvider('Hello! How can I help you?');

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
            ]);
    }

    public function test_send_message_continues_existing_conversation(): void
    {
        $conversation = AiChatConversation::factory()->create([
            'session_id' => $this->sessionId,
        ]);

        $this->fakeProvider('Follow-up response.');

        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Follow up',
            'session_id' => $this->sessionId,
            'conversation_id' => $conversation->id,
            'stream' => false,
        ]);

        $response->assertStatus(200);
    }

    public function test_send_message_with_custom_system_prompt(): void
    {
        $this->fakeProvider('Custom response.');

        $response = $this->postJson('/api/ai-chat/messages', [
            'message' => 'Hello',
            'session_id' => $this->sessionId,
            'system_prompt' => 'You are a coding assistant.',
            'stream' => false,
        ]);

        $response->assertStatus(200);
    }

    // ─── Send Message (Streaming) ───────────────────────────────────

    public function test_send_message_streaming_returns_event_stream(): void
    {
        $this->fakeProvider('Streaming response.');

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

    // ─── Widget Page: Structure & Rendering ─────────────────────────

    public function test_welcome_page_loads_with_widget_script(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('ai-chat-widget')
            ->assertSee('AIChatWidget.init');
    }

    public function test_widget_has_mount_point(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('id="ai-chat-widget-mount"', false);
    }

    public function test_widget_loads_minified_js(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('vendor/ai-chat/ai-chat-widget.min.js');
    }

    public function test_widget_init_receives_api_base_url(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('/api/ai-chat');
    }

    public function test_widget_init_receives_default_title(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('AI Assistant');
    }

    public function test_widget_init_receives_default_subtitle(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Ask me anything');
    }

    public function test_widget_init_receives_theme(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString("theme: 'light'", $page);
    }

    public function test_widget_init_receives_position(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString("position: 'bottom-right'", $page);
    }

    public function test_widget_init_receives_primary_color(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('#6366f1', $page);
    }

    public function test_widget_init_receives_welcome_message(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('Hello! How can I help you today?', $page);
    }

    public function test_widget_init_receives_fullscreen_setting(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('allowFullscreen: true', $page);
    }

    public function test_widget_init_receives_height_and_width(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('height: 600', $page);
        $this->assertStringContainsString('width: 380', $page);
    }

    public function test_widget_init_receives_show_feedback(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('showFeedback: true', $page);
    }

    public function test_widget_init_receives_show_suggestions(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('showSuggestions: true', $page);
    }

    public function test_widget_init_receives_show_branding(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('showBranding: true', $page);
    }

    public function test_widget_init_receives_suggested_prompts(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('What can you help me with?', $page);
        $this->assertStringContainsString('Summarize the project', $page);
        $this->assertStringContainsString('List available models', $page);
        $this->assertStringContainsString('Check system status', $page);
    }

    public function test_widget_init_receives_online_status(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString("onlineStatus: 'online'", $page);
    }

    public function test_widget_js_is_defensive_with_typeof_check(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString("typeof AIChatWidget !== 'undefined'", $page);
    }

    public function test_widget_mount_div_rendered_once(): void
    {
        $page = $this->get('/')->content();

        $count = substr_count($page, 'ai-chat-widget-mount');
        $this->assertEquals(1, $count);
    }
    // ─── Widget Page: Welcome Demo Section ──────────────────────────

    public function test_welcome_page_has_ai_demo_section(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('AI Chat')
            ->assertSee('Open AI Chat')
            ->assertSee('Start Demo');
    }

    public function test_welcome_page_has_open_chat_button(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('AIChatWidget && AIChatWidget.open()', $page);
    }

    public function test_welcome_page_has_start_demo_button(): void
    {
        $page = $this->get('/')->content();
        $this->assertStringContainsString('AIChatWidget.sendMessage', $page);
    }

    public function test_welcome_page_demo_shows_console_hint(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('AIChatWidget.open()')
            ->assertSee('.toggle()')
            ->assertSee('.expand()')
            ->assertSee('.reset()');
    }

    // ─── Widget Page: Custom Config Overrides ───────────────────────

    public function test_widget_theme_can_be_overridden(): void
    {
        config(['ai-chat.widget.theme' => 'dark']);

        $page = $this->get('/')->content();
        $this->assertStringContainsString("theme: 'dark'", $page);
    }

    public function test_widget_position_can_be_overridden(): void
    {
        config(['ai-chat.widget.position' => 'bottom-left']);

        $page = $this->get('/')->content();
        $this->assertStringContainsString("position: 'bottom-left'", $page);
    }

    public function test_widget_primary_color_can_be_overridden(): void
    {
        config(['ai-chat.widget.primary_color' => '#ff0000']);

        $page = $this->get('/')->content();
        $this->assertStringContainsString("primaryColor: '#ff0000'", $page);
    }

    public function test_widget_title_can_be_overridden(): void
    {
        config(['ai-chat.widget.title' => 'Custom Bot']);

        $page = $this->get('/')->content();
        $this->assertStringContainsString("title: 'Custom Bot'", $page);
    }

    public function test_widget_subtitle_can_be_overridden(): void
    {
        config(['ai-chat.widget.subtitle' => 'Custom subtitle']);

        $page = $this->get('/')->content();
        $this->assertStringContainsString("subtitle: 'Custom subtitle'", $page);
    }

    public function test_widget_welcome_message_can_be_overridden(): void
    {
        config(['ai-chat.widget.welcome_message' => 'Custom welcome']);

        $page = $this->get('/')->content();
        $this->assertStringContainsString("welcomeMessage: 'Custom welcome'", $page);
    }

    public function test_widget_auto_open_can_be_enabled(): void
    {
        config(['ai-chat.widget.auto_open' => true]);
        config(['ai-chat.widget.open_delay' => 5000]);

        $page = $this->get('/')->content();
        $this->assertStringContainsString('autoOpen: true', $page);
        $this->assertStringContainsString('openDelay: 5000', $page);
    }

    public function test_widget_allow_fullscreen_can_be_disabled(): void
    {
        config(['ai-chat.widget.allow_fullscreen' => false]);

        $page = $this->get('/')->content();
        $this->assertStringContainsString('allowFullscreen: false', $page);
    }

    public function test_widget_feedback_can_be_disabled(): void
    {
        config(['ai-chat.widget.show_feedback' => false]);

        $page = $this->get('/')->content();
        $this->assertStringContainsString('showFeedback: false', $page);
    }

    public function test_widget_suggestions_can_be_disabled(): void
    {
        config(['ai-chat.widget.show_suggestions' => false]);

        $page = $this->get('/')->content();
        $this->assertStringContainsString('showSuggestions: false', $page);
    }

    public function test_widget_suggested_prompts_can_be_customized(): void
    {
        config(['ai-chat.widget.suggested_prompts' => ['Prompt A', 'Prompt B']]);

        $page = $this->get('/')->content();
        $this->assertStringContainsString('Prompt A', $page);
        $this->assertStringContainsString('Prompt B', $page);
    }

    public function test_widget_height_and_width_can_be_customized(): void
    {
        config(['ai-chat.widget.height' => 700]);
        config(['ai-chat.widget.width' => 450]);

        $page = $this->get('/')->content();
        $this->assertStringContainsString('height: 700', $page);
        $this->assertStringContainsString('width: 450', $page);
    }

    public function test_widget_online_status_can_be_overridden(): void
    {
        config(['ai-chat.widget.online_status' => 'away']);

        $page = $this->get('/')->content();
        $this->assertStringContainsString("onlineStatus: 'away'", $page);
    }

    // ─── Widget JS: Source Code Validation ──────────────────────────

    public function test_widget_source_js_has_required_features(): void
    {
        $jsPath = base_path('packages/ai-chat/resources/assets/js/ai-chat-widget/ai-chat-widget.js');
        $this->assertFileExists($jsPath);

        $js = file_get_contents($jsPath);

        $this->assertStringContainsString('AIChatWidget', $js);
        $this->assertStringContainsString('ai-chat-bubble', $js);
        $this->assertStringContainsString('ai-chat-window', $js);
        $this->assertStringContainsString('ShadowRoot', $js);
    }

    public function test_widget_js_defines_all_public_api_methods(): void
    {
        $jsPath = base_path('packages/ai-chat/resources/assets/js/ai-chat-widget/ai-chat-widget.js');
        $this->assertFileExists($jsPath);

        $js = file_get_contents($jsPath);

        $this->assertStringContainsString('open:', $js);
        $this->assertStringContainsString('close:', $js);
        $this->assertStringContainsString('toggle:', $js);
        $this->assertStringContainsString('expand:', $js);
        $this->assertStringContainsString('reset:', $js);
        $this->assertStringContainsString('destroy:', $js);
        $this->assertStringContainsString('sendMessage:', $js);
        $this->assertStringContainsString('getState:', $js);
        $this->assertStringContainsString('VERSION:', $js);
    }

    public function test_widget_js_has_premium_features(): void
    {
        $jsPath = base_path('packages/ai-chat/resources/assets/js/ai-chat-widget/ai-chat-widget.js');
        $this->assertFileExists($jsPath);

        $js = file_get_contents($jsPath);

        $this->assertStringContainsString('sparkles', $js);
        $this->assertStringContainsString('svgIcon', $js);
        $this->assertStringContainsString('ai-badge', $js);
        $this->assertStringContainsString('ai-chat-status', $js);
        $this->assertStringContainsString('ai-chat-streaming-cursor', $js);
        $this->assertStringContainsString('stopBtn', $js);
        $this->assertStringContainsString('feedback', $js);
        $this->assertStringContainsString('skeleton', $js);
        $this->assertStringContainsString('suggestedPrompts', $js);
        $this->assertStringContainsString('onlineStatus', $js);
    }

    public function test_widget_js_supports_streaming_and_non_streaming(): void
    {
        $jsPath = base_path('packages/ai-chat/resources/assets/js/ai-chat-widget/ai-chat-widget.js');
        $this->assertFileExists($jsPath);

        $js = file_get_contents($jsPath);

        $this->assertStringContainsString('text/event-stream', $js);
        $this->assertStringContainsString('streamResponse', $js);
        $this->assertStringContainsString('SSELines', $js);
        $this->assertStringContainsString('[DONE]', $js);
        $this->assertStringContainsString('[ERROR]', $js);
    }

    public function test_widget_js_handles_markdown_rendering(): void
    {
        $jsPath = base_path('packages/ai-chat/resources/assets/js/ai-chat-widget/ai-chat-widget.js');
        $this->assertFileExists($jsPath);

        $js = file_get_contents($jsPath);

        $this->assertStringContainsString('renderMarkdown', $js);
        $this->assertStringContainsString('```', $js);
        $this->assertStringContainsString('<strong>', $js);
    }

    public function test_widget_js_escapes_html_safely(): void
    {
        $jsPath = base_path('packages/ai-chat/resources/assets/js/ai-chat-widget/ai-chat-widget.js');
        $this->assertFileExists($jsPath);

        $js = file_get_contents($jsPath);

        $this->assertStringContainsString('escapeHtml', $js);
        $this->assertStringContainsString('textContent', $js);
    }

    public function test_widget_compiled_js_exists_and_is_valid(): void
    {
        $compiledPath = public_path('vendor/ai-chat/ai-chat-widget.min.js');
        $this->assertFileExists($compiledPath);

        $size = filesize($compiledPath);
        $this->assertGreaterThan(20000, $size);
        $this->assertLessThan(100000, $size);

        $content = file_get_contents($compiledPath);
        $this->assertStringContainsString('AIChatWidget', $content);
        $this->assertStringContainsString('init', $content);
        $this->assertStringContainsString('open', $content);
    }

    public function test_widget_compiled_js_has_no_syntax_errors(): void
    {
        $compiledPath = public_path('vendor/ai-chat/ai-chat-widget.min.js');
        $this->assertFileExists($compiledPath);

        $js = file_get_contents($compiledPath);
        $error = '';

        $this->assertStringNotContainsString('SyntaxError', $error);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    private function assertArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }
}
