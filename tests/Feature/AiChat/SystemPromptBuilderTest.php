<?php

namespace Tests\Feature\AiChat;

use AiChat\Agents\ProjectAssistantAgent;
use AiChat\Pipeline\ChatPayload;
use AiChat\Prompt\SystemPromptBuilder;
use Tests\TestCase;

class SystemPromptBuilderTest extends TestCase
{
    private SystemPromptBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = app(SystemPromptBuilder::class);
    }

    public function test_returns_empty_string_when_payload_has_no_agent_or_data(): void
    {
        $payload = new ChatPayload('hello');

        // No agent, no context, no knowledge, no memory
        $result = $this->builder->build($payload);

        // Falls back to config default_system_prompt
        $this->assertIsString($result);
    }

    public function test_includes_agent_system_prompt(): void
    {
        $payload = new ChatPayload('hello');
        $payload->agent = new ProjectAssistantAgent;

        $result = $this->builder->build($payload);

        $this->assertStringContainsString('READ-ONLY', $result);
    }

    public function test_includes_context_section_when_present(): void
    {
        $payload = new ChatPayload('hello');
        $payload->context = ['user_role' => 'admin', 'tenant' => 'acme'];

        $result = $this->builder->build($payload);

        $this->assertStringContainsString('## Context', $result);
        $this->assertStringContainsString('user_role', $result);
        $this->assertStringContainsString('admin', $result);
    }

    public function test_includes_knowledge_section_when_present(): void
    {
        $payload = new ChatPayload('hello');
        $payload->knowledge = ['Refunds are allowed within 14 days.'];

        $result = $this->builder->build($payload);

        $this->assertStringContainsString('## Knowledge Base', $result);
        $this->assertStringContainsString('[1]', $result);
        $this->assertStringContainsString('Refunds are allowed within 14 days', $result);
    }

    public function test_includes_memory_section_when_present(): void
    {
        $payload = new ChatPayload('hello');
        $payload->memory = ['User name is Hassan.'];

        $result = $this->builder->build($payload);

        $this->assertStringContainsString('## Conversation Memory', $result);
        $this->assertStringContainsString('Hassan', $result);
    }

    public function test_combines_all_sections_in_order(): void
    {
        $payload = new ChatPayload('hello');
        $payload->agent = new ProjectAssistantAgent;
        $payload->context = ['key' => 'value'];
        $payload->knowledge = ['Some knowledge.'];
        $payload->memory = ['Some memory.'];

        $result = $this->builder->build($payload);

        $agentPos = strpos($result, 'READ-ONLY');
        $contextPos = strpos($result, '## Context');
        $knowledgePos = strpos($result, '## Knowledge Base');
        $memoryPos = strpos($result, '## Conversation Memory');

        $this->assertNotFalse($agentPos);
        $this->assertNotFalse($contextPos);
        $this->assertNotFalse($knowledgePos);
        $this->assertNotFalse($memoryPos);

        // Agent prompt comes first, then context, knowledge, memory
        $this->assertLessThan($contextPos, $agentPos);
        $this->assertLessThan($knowledgePos, $contextPos);
        $this->assertLessThan($memoryPos, $knowledgePos);
    }

    public function test_omits_context_section_when_empty(): void
    {
        $payload = new ChatPayload('hello');
        $payload->context = [];

        $result = $this->builder->build($payload);

        $this->assertStringNotContainsString('## Context', $result);
    }

    public function test_omits_knowledge_section_when_empty(): void
    {
        $payload = new ChatPayload('hello');
        $payload->knowledge = [];

        $result = $this->builder->build($payload);

        $this->assertStringNotContainsString('## Knowledge Base', $result);
    }

    public function test_omits_memory_section_when_empty(): void
    {
        $payload = new ChatPayload('hello');
        $payload->memory = [];

        $result = $this->builder->build($payload);

        $this->assertStringNotContainsString('## Conversation Memory', $result);
    }

    public function test_is_registered_as_singleton_in_container(): void
    {
        $a = app(SystemPromptBuilder::class);
        $b = app(SystemPromptBuilder::class);

        $this->assertSame($a, $b);
    }
}
