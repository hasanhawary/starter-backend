<?php

namespace AiChat\Tests\Pipeline;

use AiChat\Contracts\AgentInterface;
use AiChat\Memory\MemoryRetriever;
use AiChat\Pipeline\ChatPayload;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Pipeline\Steps\RetrieveMemory;
use AiChat\Storage\AnonymousConversationStore;
use Illuminate\Contracts\Auth\Authenticatable;
use Mockery as m;
use Tests\TestCase;

class RetrieveMemoryStepTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_use_memory_true_calls_memory_retriever(): void
    {
        $conversationStore = m::mock(AnonymousConversationStore::class);
        $memoryRetriever = m::mock(MemoryRetriever::class);

        $memoryRetriever->shouldReceive('retrieve')
            ->once()
            ->with('user name', [
                'user_id' => '1',
                'guest_id' => 'guest-1',
                'tenant_id' => 'tenant-1',
                'agent_id' => 'project_assistant',
            ], 3)
            ->andReturn([
                ['content' => 'User name: حسن', 'score' => 1.0],
            ]);

        $payload = new ChatPayload('فاكر اسمي؟', $this->authUser(1));
        $payload->metadata = [
            'guest_id' => 'guest-1',
            'tenant_id' => 'tenant-1',
        ];
        $payload->agent = $this->agent('project_assistant');
        $payload->executionPlan = tap(new ExecutionPlan, function (ExecutionPlan $plan): void {
            $plan->useMemory = true;
            $plan->memoryQuery = 'user name';
            $plan->memoryLimit = 3;
        });

        $step = new RetrieveMemory($conversationStore, $memoryRetriever);
        $result = $step->handle($payload, fn (ChatPayload $payload) => $payload);

        $this->assertSame(['User name: حسن'], $result->memory);
    }

    public function test_use_memory_false_skips_memory_retrieval(): void
    {
        $conversationStore = m::mock(AnonymousConversationStore::class);
        $memoryRetriever = m::mock(MemoryRetriever::class);

        $memoryRetriever->shouldNotReceive('retrieve');

        $payload = new ChatPayload('ازيك', $this->authUser(1));
        $payload->metadata = ['guest_id' => 'guest-1'];
        $payload->executionPlan = tap(new ExecutionPlan, function (ExecutionPlan $plan): void {
            $plan->useMemory = false;
        });

        $step = new RetrieveMemory($conversationStore, $memoryRetriever);
        $result = $step->handle($payload, fn (ChatPayload $payload) => $payload);

        $this->assertSame([], $result->memory);
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
