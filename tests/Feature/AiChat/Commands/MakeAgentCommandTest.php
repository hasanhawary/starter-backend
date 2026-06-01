<?php

namespace Tests\Feature\AiChat\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeAgentCommandTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputPath = app_path('AI/Agents');
    }

    protected function tearDown(): void
    {
        // Remove only test-generated agents, not the real ProjectAssistantAgent
        foreach (['TestAgent.php', 'SupportAgent.php', 'OrdersAgent.php', 'ValidSyntaxAgent.php'] as $file) {
            File::delete($this->outputPath.'/'.$file);
        }
        parent::tearDown();
    }

    public function test_creates_agent_file(): void
    {
        $this->artisan('ai-chat:make-agent', ['name' => 'Test'])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/TestAgent.php');
    }

    public function test_appends_agent_suffix_when_missing(): void
    {
        $this->artisan('ai-chat:make-agent', ['name' => 'support'])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/SupportAgent.php');
    }

    public function test_does_not_double_append_agent_suffix(): void
    {
        $this->artisan('ai-chat:make-agent', ['name' => 'OrdersAgent'])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/OrdersAgent.php');
        $this->assertFileDoesNotExist($this->outputPath.'/OrdersAgentAgent.php');
    }

    public function test_generated_agent_extends_base_agent(): void
    {
        $this->artisan('ai-chat:make-agent', ['name' => 'Test'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/TestAgent.php');

        $this->assertStringContainsString('extends BaseAgent', $content);
        $this->assertStringContainsString('namespace App\\AI\\Agents', $content);
        $this->assertStringContainsString('use AiChat\\Agents\\BaseAgent', $content);
    }

    public function test_generated_agent_has_required_properties(): void
    {
        $this->artisan('ai-chat:make-agent', ['name' => 'Test'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/TestAgent.php');

        $this->assertStringContainsString('protected string $name', $content);
        $this->assertStringContainsString('protected string $description', $content);
        $this->assertStringContainsString('protected string $systemPrompt', $content);
        $this->assertStringContainsString('protected array $tools', $content);
        $this->assertStringContainsString('protected array $policies', $content);
        $this->assertStringContainsString('protected bool $readOnly', $content);
    }

    public function test_generated_agent_has_default_read_only_policy(): void
    {
        $this->artisan('ai-chat:make-agent', ['name' => 'Test'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/TestAgent.php');

        $this->assertStringContainsString('DefaultReadOnlyPolicy', $content);
        // Ensure no doubled namespace
        $this->assertStringNotContainsString('AiChat\\AiChat\\', $content);
    }

    public function test_generated_agent_is_valid_php(): void
    {
        $this->artisan('ai-chat:make-agent', ['name' => 'ValidSyntax'])
            ->assertSuccessful();

        $filePath = $this->outputPath.'/ValidSyntaxAgent.php';
        $output = shell_exec("php -l {$filePath} 2>&1");

        $this->assertStringContainsString('No syntax errors', $output);
    }
}
