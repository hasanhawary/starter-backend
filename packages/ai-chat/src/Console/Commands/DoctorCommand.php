<?php

namespace AiChat\Console\Commands;

use AiChat\MCP\ToolRegistry;
use AiChat\Models\AiKnowledgeDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DoctorCommand extends Command
{
    protected $signature = 'ai-chat:doctor';

    protected $description = 'Check AI Chat package health';

    public function handle(): int
    {
        $this->components->info('Running AI Chat health checks...');
        $this->newLine();

        $allPassed = true;

        $allPassed = $this->checkConfig($allPassed);
        $allPassed = $this->checkApiKey($allPassed);
        $allPassed = $this->checkProviderReachable($allPassed);
        $allPassed = $this->checkDatabaseMigrated($allPassed);
        $allPassed = $this->checkWidgetAssets($allPassed);
        $allPassed = $this->checkToolsDiscovered($allPassed);
        $allPassed = $this->checkAgentsDiscovered($allPassed);
        $allPassed = $this->checkKnowledgeIndexed($allPassed);
        $allPassed = $this->checkVectorDriver($allPassed);

        $this->newLine();

        if ($allPassed) {
            $this->components->info('All checks passed!');
        } else {
            $this->components->warn('Some checks failed. Please review the output above.');
        }

        return $allPassed ? self::SUCCESS : self::FAILURE;
    }

    protected function checkConfig(bool $allPassed): bool
    {
        $exists = File::exists(config_path('ai-chat.php'));

        $this->displayCheck('Config file exists', $exists);

        return $exists ? $allPassed : false;
    }

    protected function checkApiKey(bool $allPassed): bool
    {
        $key = config('ai-chat.api_key') ?? env('GLM_API_KEY');
        $set = filled($key);

        $this->displayCheck('API key is set', $set);

        return $set ? $allPassed : false;
    }

    protected function checkProviderReachable(bool $allPassed): bool
    {
        $baseUrl = config('ai-chat.provider.base_url', 'https://open.bigmodel.cn/api/paas/v4');

        try {
            $response = Http::timeout(5)->get($baseUrl);

            $reachable = $response->successful() || $response->status() === 401 || $response->status() === 403;
        } catch (\Throwable) {
            $reachable = false;
        }

        $this->displayCheck('Provider is reachable', $reachable);

        return $reachable ? $allPassed : false;
    }

    protected function checkDatabaseMigrated(bool $allPassed): bool
    {
        try {
            $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');
            $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');

            $migrated = \Schema::hasTable($conversationsTable)
                && \Schema::hasTable($messagesTable)
                && \Schema::hasTable('ai_tool_calls');
        } catch (\Throwable) {
            $migrated = false;
        }

        $this->displayCheck('Database tables migrated', $migrated);

        return $migrated ? $allPassed : false;
    }

    protected function checkWidgetAssets(bool $allPassed): bool
    {
        $published = File::exists(public_path('vendor/ai-chat/js/ai-chat-widget/ai-chat-widget.js'));

        $this->displayCheck('Widget assets published', $published);

        return $published ? $allPassed : false;
    }

    protected function checkToolsDiscovered(bool $allPassed): bool
    {
        try {
            $registry = app(ToolRegistry::class);
            $count = count($registry->all());
            $hasTools = $count > 0;

            $this->displayCheck("Tools discovered ({$count})", $hasTools);
        } catch (\Throwable) {
            $this->displayCheck('Tools discovered', false);

            return false;
        }

        return $hasTools ? $allPassed : false;
    }

    protected function checkAgentsDiscovered(bool $allPassed): bool
    {
        $agentPath = app_path('AI/Agents');
        $hasAgents = File::isDirectory($agentPath)
            && count(File::glob($agentPath.'/*.php')) > 0;

        $count = $hasAgents ? count(File::glob($agentPath.'/*.php')) : 0;

        $this->displayCheck("Agents discovered ({$count})", $hasAgents);

        return $hasAgents ? $allPassed : false;
    }

    protected function checkKnowledgeIndexed(bool $allPassed): bool
    {
        try {
            $count = AiKnowledgeDocument::count();
            $hasDocuments = $count > 0;

            $this->displayCheck("Knowledge indexed ({$count} documents)", $hasDocuments);
        } catch (\Throwable) {
            $this->displayCheck('Knowledge indexed', false);

            return false;
        }

        return $hasDocuments ? $allPassed : false;
    }

    protected function checkVectorDriver(bool $allPassed): bool
    {
        $driver = config('ai-chat.vector.driver', 'database');

        $configured = filled($driver);

        $this->displayCheck("Vector driver configured [{$driver}]", $configured);

        return $configured ? $allPassed : false;
    }

    protected function displayCheck(string $label, bool $passed): void
    {
        $status = $passed ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $this->components->twoColumnDetail($label, $status);
    }
}
