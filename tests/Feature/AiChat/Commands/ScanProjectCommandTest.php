<?php

namespace Tests\Feature\AiChat\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ScanProjectCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $generatedPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generatedPath = app_path('AI/Tools/Generated');
    }

    public function test_scan_without_generate_displays_results(): void
    {
        $this->artisan('ai-chat:scan')
            ->assertSuccessful()
            ->expectsOutputToContain('Scan Results')
            ->expectsOutputToContain('Models')
            ->expectsOutputToContain('Routes');
    }

    public function test_scan_without_generate_shows_hint(): void
    {
        $this->artisan('ai-chat:scan')
            ->assertSuccessful()
            ->expectsOutputToContain('--generate');
    }

    public function test_scan_with_generate_creates_tool_files(): void
    {
        $this->artisan('ai-chat:scan', ['--generate' => true])
            ->assertSuccessful();

        // At minimum the User model tools should be generated
        $this->assertFileExists($this->generatedPath.'/UserCountTool.php');
        $this->assertFileExists($this->generatedPath.'/UserSearchTool.php');
        $this->assertFileExists($this->generatedPath.'/UserLatestRecordsTool.php');
        $this->assertFileExists($this->generatedPath.'/UserStatsTool.php');
    }

    public function test_generated_count_tool_includes_safe_query_builder_import(): void
    {
        $this->artisan('ai-chat:scan', ['--generate' => true])
            ->assertSuccessful();

        $content = File::get($this->generatedPath.'/UserCountTool.php');

        $this->assertStringContainsString('use AiChat\\Support\\SafeQueryBuilder', $content);
    }

    public function test_generated_tools_are_valid_php(): void
    {
        $this->artisan('ai-chat:scan', ['--generate' => true])
            ->assertSuccessful();

        $files = File::glob($this->generatedPath.'/*.php');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $output = shell_exec("php -l {$file} 2>&1");
            $this->assertStringContainsString('No syntax errors', $output, "Syntax error in {$file}");
        }
    }

    public function test_scan_with_generate_skips_blocked_models(): void
    {
        $this->artisan('ai-chat:scan', ['--generate' => true])
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->generatedPath.'/PersonalAccessTokenCountTool.php');
        $this->assertFileDoesNotExist($this->generatedPath.'/PasswordResetTokenCountTool.php');
    }

    public function test_scan_with_generate_outputs_generated_count(): void
    {
        $this->artisan('ai-chat:scan', ['--generate' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('tool(s) generated successfully');
    }

    public function test_scan_with_generate_creates_manifest(): void
    {
        $this->artisan('ai-chat:scan', ['--generate' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('ai-project.php');
    }
}
