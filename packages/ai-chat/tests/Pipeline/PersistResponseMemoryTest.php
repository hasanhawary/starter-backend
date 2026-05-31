<?php

namespace AiChat\Tests\Pipeline;

use AiChat\Chat\ConversationManager;
use AiChat\Chat\MessageManager;
use AiChat\Contracts\AgentInterface;
use AiChat\Memory\MemoryExtractor;
use AiChat\Models\AiChatConversation;
use AiChat\Pipeline\ChatPayload;
use AiChat\Pipeline\Steps\PersistResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Mockery as m;
use Tests\TestCase;

class PersistResponseMemoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_memory_extractor_is_called_after_final_assistant_response(): void
    {
        $messageManager = m::mock(MessageManager::class);
        $conversationManager = m::mock(ConversationManager::class);
        $memoryExtractor = m::mock(MemoryExtractor::class);

        $conversation = new AiChatConversation;
        $conversation->id = 'conv-1';

        $payload = new ChatPayload('اسمي حسن', $this->authUser(1));
        $payload->conversation = $conversation;
        $payload->response = 'أهلاً حسن';
        $payload->metadata = [
            'session_id' => 'session-1',
            'tenant_id' => 'tenant-1',
        ];
        $payload->agent = $this->agent('project_assistant');

        $messageManager->shouldReceive('storeUserMessage')->once();
        $messageManager->shouldReceive('storeAssistantMessage')->once();
        $memoryExtractor->shouldReceive('extractFromExchange')
            ->once()
            ->with(
                'conv-1',
                'اسمي حسن',
                'أهلاً حسن',
                m::on(function (array $context): bool {
                    return $context['user_id'] === '1'
                        && $context['guest_id'] === 'session-1'
                        && $context['tenant_id'] === 'tenant-1'
                        && $context['agent_id'] === 'project_assistant';
                }),
            )
            ->andReturn([
                'id' => 'mem-1',
                'conversation_id' => 'conv-1',
                'content' => 'User name: حسن',
                'metadata' => ['type' => 'user_profile'],
            ]);
        $memoryExtractor->shouldReceive('store')->once()->with(m::on(function (array $memoryData): bool {
            return $memoryData['content'] === 'User name: حسن';
        }))->andReturnTrue();

        $step = new PersistResponse($messageManager, $conversationManager, $memoryExtractor);

        $result = $step->handle($payload, fn (ChatPayload $payload) => $payload);

        $this->assertSame($payload, $result);
    }

    protected function authUser(int $id): Authenticatable
    {
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn((string) $id);

        return $user;
    }

    protected function agent(string $name): AgentInterface
    {
        $agent = m::mock(AgentInterface::class);
        $agent->shouldReceive('name')->andReturn($name);
        $agent->shouldReceive('description')->andReturn('');
        $agent->shouldReceive('systemPrompt')->andReturn('');
        $agent->shouldReceive('tools')->andReturn([]);
        $agent->shouldReceive('contextProviders')->andReturn([]);
        $agent->shouldReceive('policies')->andReturn([]);
        $agent->shouldReceive('isReadOnly')->andReturn(true);

        return $agent;
    }
}
