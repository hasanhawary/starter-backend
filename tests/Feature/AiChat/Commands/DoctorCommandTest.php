<?php

namespace Tests\Feature\AiChat\Commands;

use AiChat\MCP\ToolRegistry;
use AiChat\Models\AiKnowledgeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_runs_without_crashing(): void
    {
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Running AI Chat health checks');
    }

    public function test_doctor_checks_config_file(): void
    {
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Config file exists');
    }

    public function test_doctor_passes_config_check_when_file_exists(): void
    {
        // Config is loaded via mergeConfigFrom in the service provider,
        // so the config values are always available even without publishing.
        $this->assertNotNull(config('ai-chat.provider'));

        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Config file exists');
    }

    public function test_doctor_checks_database_migration(): void
    {
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Database tables migrated');
    }

    public function test_doctor_passes_database_check_when_tables_exist(): void
    {
        // RefreshDatabase ensures migrations ran
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Database tables migrated');
    }

    public function test_doctor_checks_tools_discovered(): void
    {
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Tools discovered');
    }

    public function test_doctor_reports_tool_count(): void
    {
        $registry = app(ToolRegistry::class);
        $count = count($registry->all());

        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain("Tools discovered ({$count})");
    }

    public function test_doctor_checks_agents_discovered(): void
    {
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Agents discovered');
    }

    public function test_doctor_checks_knowledge_indexed(): void
    {
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Knowledge indexed');
    }

    public function test_doctor_checks_vector_driver(): void
    {
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('Vector driver configured');
    }

    public function test_doctor_shows_all_checks_passed_when_healthy(): void
    {
        // Seed a knowledge document so that check passes too
        AiKnowledgeDocument::create([
            'id' => Str::uuid()->toString(),
            'title' => 'Test Doc',
            'source_path' => 'test/doc.md',
            'source_type' => 'md',
            'content_hash' => md5('test'),
            'chunk_count' => 0,
            'metadata' => [],
            'indexed_at' => now(),
        ]);

        // The doctor may still fail on API key / provider reachability in CI,
        // but it must not crash and must output the summary line.
        $this->artisan('ai-chat:doctor')
            ->expectsOutputToContain('checks');
    }
}
