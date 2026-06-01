<?php

namespace Tests\Feature\AiChat\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeToolCommandTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputPath = app_path('AI/Tools/Custom');
    }

    protected function tearDown(): void
    {
        // Clean up generated files after each test
        File::deleteDirectory($this->outputPath);
        parent::tearDown();
    }

    public function test_creates_tool_file_with_correct_class_name(): void
    {
        $this->artisan('ai-chat:make-tool', ['name' => 'OrderStats'])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/OrderStatsTool.php');
    }

    public function test_appends_tool_suffix_when_missing(): void
    {
        $this->artisan('ai-chat:make-tool', ['name' => 'revenue-report'])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/RevenueReportTool.php');
    }

    public function test_does_not_double_append_tool_suffix(): void
    {
        $this->artisan('ai-chat:make-tool', ['name' => 'OrderStatsTool'])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/OrderStatsTool.php');
        $this->assertFileDoesNotExist($this->outputPath.'/OrderStatsToolTool.php');
    }

    public function test_generated_tool_implements_tool_interface(): void
    {
        $this->artisan('ai-chat:make-tool', ['name' => 'SalesReport'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/SalesReportTool.php');

        $this->assertStringContainsString('implements ToolInterface', $content);
        $this->assertStringContainsString('namespace App\\AI\\Tools\\Custom', $content);
        $this->assertStringContainsString('use AiChat\\Contracts\\ToolInterface', $content);
        $this->assertStringContainsString('use AiChat\\MCP\\ToolResult', $content);
        $this->assertStringContainsString('use AiChat\\Policies\\ChatContext', $content);
        $this->assertStringContainsString('use AiChat\\Support\\SafeQueryBuilder', $content);
    }

    public function test_generated_tool_has_required_methods(): void
    {
        $this->artisan('ai-chat:make-tool', ['name' => 'ProductCount'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/ProductCountTool.php');

        $this->assertStringContainsString('public function name()', $content);
        $this->assertStringContainsString('public function description()', $content);
        $this->assertStringContainsString('public function schema()', $content);
        $this->assertStringContainsString('public function authorize(ChatContext $context): bool', $content);
        $this->assertStringContainsString('public function execute(array $arguments, ChatContext $context): ToolResult', $content);
    }

    public function test_generated_tool_has_correct_tool_name(): void
    {
        $this->artisan('ai-chat:make-tool', ['name' => 'UserActivity'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/UserActivityTool.php');

        $this->assertStringContainsString("return 'user_activity'", $content);
    }

    public function test_fails_when_tool_already_exists(): void
    {
        File::ensureDirectoryExists($this->outputPath);
        File::put($this->outputPath.'/DuplicateTool.php', '<?php // existing');

        $this->artisan('ai-chat:make-tool', ['name' => 'Duplicate'])
            ->assertFailed();
    }

    public function test_creates_tool_with_model_option(): void
    {
        $this->artisan('ai-chat:make-tool', ['name' => 'UserList', '--model' => 'User'])
            ->assertSuccessful();

        $content = File::get($this->outputPath.'/UserListTool.php');

        $this->assertStringContainsString('use App\\Models\\User', $content);
    }

    public function test_generated_tool_is_valid_php(): void
    {
        $this->artisan('ai-chat:make-tool', ['name' => 'ValidSyntax'])
            ->assertSuccessful();

        $filePath = $this->outputPath.'/ValidSyntaxTool.php';
        $output = shell_exec("php -l {$filePath} 2>&1");

        $this->assertStringContainsString('No syntax errors', $output);
    }
}
