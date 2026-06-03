<?php

namespace Tests\Feature\AiChat;

use AiChat\Agents\ChatAgent;
use AiChat\Agents\ProjectAssistantAgent;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Prompt\OutputContract;
use Tests\TestCase;

class OutputContractTest extends TestCase
{
    // -----------------------------------------------------------------------
    // OutputContract::rule()
    // -----------------------------------------------------------------------

    public function test_output_contract_rule_is_non_empty_string(): void
    {
        $rule = OutputContract::rule();

        $this->assertIsString($rule);
        $this->assertNotEmpty($rule);
    }

    public function test_output_contract_rule_forbids_tool_leak_phrases(): void
    {
        $rule = OutputContract::rule();

        $this->assertStringContainsString('I have the X function available', $rule);
        $this->assertStringContainsString('I will call', $rule);
        $this->assertStringContainsString("I'll call", $rule);
        $this->assertStringContainsString('The data shows', $rule);
        $this->assertStringContainsString('The function returned', $rule);
        $this->assertStringContainsString('The tool returned', $rule);
        $this->assertStringContainsString('with no errors', $rule);
        $this->assertStringContainsString('From the knowledge base', $rule);
        $this->assertStringContainsString('From source [N]', $rule);
    }

    public function test_output_contract_tool_rule_is_non_empty_string(): void
    {
        $rule = OutputContract::toolRule();

        $this->assertIsString($rule);
        $this->assertNotEmpty($rule);
    }

    // -----------------------------------------------------------------------
    // ProjectAssistantAgent uses OutputContract
    // -----------------------------------------------------------------------

    public function test_project_assistant_agent_system_prompt_starts_with_output_contract(): void
    {
        $agent = new ProjectAssistantAgent;

        $this->assertStringStartsWith(OutputContract::rule(), $agent->systemPrompt());
    }

    public function test_project_assistant_agent_system_prompt_contains_guidelines(): void
    {
        $agent = new ProjectAssistantAgent;

        $this->assertStringContainsString('READ-ONLY', $agent->systemPrompt());
        $this->assertStringContainsString('PREFER EVIDENCE', $agent->systemPrompt());
    }

    // -----------------------------------------------------------------------
    // ChatAgent::instructions() uses OutputContract
    // -----------------------------------------------------------------------

    public function test_chat_agent_instructions_contain_output_contract_rule(): void
    {
        $agent = new ChatAgent('Custom system prompt');

        $instructions = (string) $agent->instructions();

        $this->assertStringContainsString(OutputContract::rule(), $instructions);
    }

    public function test_chat_agent_instructions_with_tool_plan_contain_tool_rule(): void
    {
        $plan = new ExecutionPlan;
        $plan->intent = 'live_data';
        $plan->tools = ['user_count'];
        $plan->useRag = false;
        $plan->useMemory = false;
        $plan->needsClarification = false;
        $plan->historyMode = 'none';
        $plan->historyLimit = 0;

        $agent = new ChatAgent;
        $agent->withExecutionPlan($plan);

        $instructions = (string) $agent->instructions();

        $this->assertStringContainsString(OutputContract::toolRule(), $instructions);
        // Must NOT contain the old verbose tool instruction
        $this->assertStringNotContainsString('The user is asking about live application data', $instructions);
    }

    public function test_chat_agent_instructions_do_not_contain_old_output_protocol_string(): void
    {
        $agent = new ChatAgent;

        $instructions = (string) $agent->instructions();

        // The old hardcoded string must be gone — replaced by OutputContract::rule()
        $this->assertStringNotContainsString('Output protocol: return only the final user-facing answer', $instructions);
    }

    public function test_output_contract_rule_is_same_string_every_call(): void
    {
        $this->assertSame(OutputContract::rule(), OutputContract::rule());
    }
}
