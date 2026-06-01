<?php

namespace Tests\Feature\AiChat\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakePolicyCommandTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputPath = app_path('AI/Policies');
    }

    protected function tearDown(): void
    {
        // Remove only test-generated policies, not the real ProjectChatPolicy
        foreach (['TestPolicy.php', 'OrdersPolicy.php', 'ValidSyntaxPolicy.php'] as $file) {
            File::delete($this->outputPath.'/'.$file);
        }
        parent::tearDown();
    }

    public function test_creates_policy_file(): void
    {
        $this->artisan('ai-chat:make-policy', ['name' => 'Test'])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/TestPolicy.php');
    }

    public function test_appends_policy_suffix_when_missing(): void
    {
        $this->artisan('ai-chat:make-policy', ['name' => 'orders'])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/OrdersPolicy.php');
    }

    public function test_generated_policy_implements_chat_policy_interface(): void
    {
        $this->artisan('ai-chat:make-policy', ['name' => 'Test'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/TestPolicy.php');

        $this->assertStringContainsString('implements ChatPolicyInterface', $content);
        $this->assertStringContainsString('namespace App\\AI\\Policies', $content);
        $this->assertStringContainsString('use AiChat\\Contracts\\ChatPolicyInterface', $content);
        $this->assertStringContainsString('use AiChat\\Policies\\ChatContext', $content);
        $this->assertStringContainsString('use AiChat\\Policies\\PolicyResult', $content);
    }

    public function test_generated_policy_has_authorize_method(): void
    {
        $this->artisan('ai-chat:make-policy', ['name' => 'Test'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/TestPolicy.php');

        $this->assertStringContainsString('public function authorize(ChatContext $context): PolicyResult', $content);
    }

    public function test_generated_policy_blocks_sensitive_models(): void
    {
        $this->artisan('ai-chat:make-policy', ['name' => 'Test'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/TestPolicy.php');

        $this->assertStringContainsString('PersonalAccessToken', $content);
        $this->assertStringContainsString('PasswordResetToken', $content);
    }

    public function test_generated_policy_blocks_sensitive_fields(): void
    {
        $this->artisan('ai-chat:make-policy', ['name' => 'Test'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/TestPolicy.php');

        $this->assertStringContainsString("'password'", $content);
        $this->assertStringContainsString("'remember_token'", $content);
        $this->assertStringContainsString("'api_key'", $content);
    }

    public function test_generated_policy_returns_policy_result(): void
    {
        $this->artisan('ai-chat:make-policy', ['name' => 'Test'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/TestPolicy.php');

        $this->assertStringContainsString('PolicyResult::denied(', $content);
        $this->assertStringContainsString('PolicyResult::allowed(', $content);
    }

    public function test_generated_policy_is_valid_php(): void
    {
        $this->artisan('ai-chat:make-policy', ['name' => 'ValidSyntax'])
            ->assertSuccessful();

        $filePath = $this->outputPath.'/ValidSyntaxPolicy.php';
        $output = shell_exec("php -l {$filePath} 2>&1");

        $this->assertStringContainsString('No syntax errors', $output);
    }
}
