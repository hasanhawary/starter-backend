<?php

namespace AiChat\Console\Commands;

use AiChat\Support\ProviderCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AiChatInstallCommand extends Command
{
    protected $signature = 'ai-chat:install {--preset=blank : Install preset} {--force : Overwrite existing files} {--skip-provider : Skip provider configuration}';

    protected $description = 'Install the AI Chat module';

    protected array $presets = ['blank', 'admin', 'ecommerce', 'crm', 'erp', 'saas'];

    protected array $aiDirectories = [
        'Agents',
        'Tools/Generated',
        'Tools/Custom',
        'Policies',
        'ContextProviders',
        'Knowledge',
        'Memory',
    ];

    public function handle(): void
    {
        $this->components->info('Installing AI Chat Module...');

        $preset = $this->option('preset');

        if (! in_array($preset, $this->presets, true)) {
            $this->components->error("Invalid preset [{$preset}]. Available: ".implode(', ', $this->presets));

            return;
        }

        $this->publishConfigs();
        $this->configureProvider();
        $this->runMigrations();
        $this->createAiDirectoryStructure();
        $this->installDefaultAgent();
        $this->installDefaultPolicy();
        $this->installDefaultKnowledge();
        $this->installPreset($preset);
        $this->publishWidgetAssets();
        $this->buildWidget();

        $this->newLine();
        $this->components->info('AI Chat module installed successfully!');
        $this->displayUsage();
    }

    protected function configureProvider(): void
    {
        if ($this->option('skip-provider')) {
            return;
        }

        if (! $this->input->isInteractive()) {
            $this->components->warn('Non-interactive mode — skipping provider configuration. Set AI_CHAT_PROVIDER and AI_CHAT_MODEL in .env manually.');

            return;
        }

        $this->newLine();
        $this->components->info('Configuring AI Provider...');

        $providerKey = $this->selectProvider();
        $model = $this->selectModel($providerKey);
        $this->writeProviderEnv($providerKey, $model);
    }

    protected function selectProvider(): string
    {
        $groups = ProviderCatalog::providerGroups();
        $allProviders = ProviderCatalog::providers();

        $options = [];

        foreach ($groups as $groupLabel => $providerKeys) {
            foreach ($providerKeys as $key) {
                if (isset($allProviders[$key])) {
                    $options[$key] = "[{$groupLabel}] {$allProviders[$key]['name']}";
                }
            }
        }

        $selected = select(
            label: 'Which AI provider do you want to use?',
            options: $options,
            default: 'glm',
            scroll: 15,
        );

        return $selected;
    }

    protected function selectModel(string $providerKey): string
    {
        $models = ProviderCatalog::models($providerKey);

        if (empty($models)) {
            return $this->selectCustomModel($providerKey);
        }

        $options = [];

        foreach ($models as $modelId => $info) {
            $tier = match ($info['tier'] ?? 'default') {
                'smartest' => '⭐',
                'default' => '✓',
                'cheapest' => '⚡',
                default => ' ',
            };
            $suffix = ($info['default'] ?? false) ? ' (Recommended)' : '';
            $options[$modelId] = "{$tier} {$info['name']}{$suffix}";
        }

        $options['__custom__'] = '✏️  Type a custom model name...';

        $selected = select(
            label: 'Which model do you want to use?',
            options: $options,
            default: ProviderCatalog::defaultModel($providerKey),
            scroll: 15,
        );

        if ($selected === '__custom__') {
            return $this->selectCustomModel($providerKey);
        }

        return $selected;
    }

    protected function selectCustomModel(string $providerKey): string
    {
        $provider = ProviderCatalog::providers()[$providerKey] ?? null;

        $hint = match ($providerKey) {
            'ollama' => 'e.g. llama3.1:8b, qwen2.5:7b, codellama:13b',
            'openrouter' => 'e.g. anthropic/claude-sonnet-4.6, google/gemini-3-flash-preview',
            'custom' => 'e.g. llama3, mistral, codellama',
            default => 'e.g. my-fine-tuned-model',
        };

        return text(
            label: 'Enter the model name',
            placeholder: $hint,
            required: true,
        );
    }

    protected function writeProviderEnv(string $providerKey, string $model): void
    {
        $provider = ProviderCatalog::providers()[$providerKey];
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->components->warn('.env file not found. Skipping .env configuration.');

            return;
        }

        $this->setEnvValue($envPath, 'AI_CHAT_PROVIDER', $providerKey);
        $this->setEnvValue($envPath, 'AI_CHAT_MODEL', $model);

        if ($provider['env_key'] && $providerKey !== 'ollama' && $providerKey !== 'custom') {
            $existingValue = env($provider['env_key']);
            $existingInFile = $this->getEnvValue($envPath, $provider['env_key']);

            if (empty($existingInFile)) {
                $apiKey = text(
                    label: "Enter your {$provider['name']} API key",
                    placeholder: 'sk-...',
                    validate: fn (string $value) => strlen($value) >= 3 ? null : 'API key must be at least 3 characters',
                );

                $this->setEnvValue($envPath, $provider['env_key'], $apiKey);
            } else {
                $this->components->twoColumnDetail($provider['env_key'], '<fg=green>Already set</>');
            }
        }

        if ($providerKey === 'custom') {
            $customUrl = text(
                label: 'Enter your custom API base URL',
                placeholder: 'http://localhost:11434/v1',
                required: true,
            );

            $this->setEnvValue($envPath, 'AI_CHAT_CUSTOM_URL', $customUrl);

            $customKey = text(
                label: 'Enter API key (leave empty if not required)',
                placeholder: 'Optional — many local models need no key',
            );

            $this->setEnvValue($envPath, 'AI_CHAT_CUSTOM_KEY', $customKey);
        }

        if ($providerKey === 'azure_openai') {
            $azureUrl = text(
                label: 'Enter your Azure OpenAI endpoint URL',
                placeholder: 'https://your-resource.openai.azure.com',
                required: true,
            );

            $this->setEnvValue($envPath, 'AZURE_OPENAI_URL', $azureUrl);

            $deployment = text(
                label: 'Enter your deployment name',
                placeholder: 'gpt-4o',
                required: true,
            );

            $this->setEnvValue($envPath, 'AZURE_OPENAI_DEPLOYMENT', $deployment);
        }

        if ($providerKey === 'ollama') {
            $this->setEnvValue($envPath, 'OLLAMA_URL', 'http://localhost:11434');
        }

        $this->components->info("Provider configured: {$provider['name']} / {$model}");
    }

    protected function setEnvValue(string $envPath, string $key, string $value): void
    {
        $content = File::get($envPath);

        if (str_contains($content, "{$key}=")) {
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
        } else {
            $content = rtrim($content)."\n{$key}={$value}\n";
        }

        File::put($envPath, $content);
    }

    protected function getEnvValue(string $envPath, string $key): ?string
    {
        if (! File::exists($envPath)) {
            return null;
        }

        $content = File::get($envPath);

        if (preg_match("/^{$key}=(.*)$/m", $content, $matches)) {
            return trim($matches[1]) ?: null;
        }

        return null;
    }

    protected function publishConfigs(): void
    {
        $force = $this->option('force');

        $this->call('vendor:publish', [
            '--tag' => 'ai-chat-config',
            '--force' => $force,
        ]);
    }

    protected function runMigrations(): void
    {
        $this->call('migrate', ['--force' => true]);
    }

    protected function createAiDirectoryStructure(): void
    {
        foreach ($this->aiDirectories as $directory) {
            $path = app_path("AI/{$directory}");

            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
                $this->components->task("Created app/AI/{$directory}", fn () => true);
            } else {
                $this->components->twoColumnDetail("app/AI/{$directory}", '<fg=yellow>Already exists</>');
            }
        }
    }

    protected function installDefaultAgent(): void
    {
        $path = app_path('AI/Agents/ProjectAssistantAgent.php');

        if (File::exists($path) && ! $this->option('force')) {
            $this->components->twoColumnDetail('ProjectAssistantAgent', '<fg=yellow>Already exists</>');

            return;
        }

        $content = <<<'PHP'
<?php

namespace App\AI\Agents;

use AiChat\Agents\BaseAgent;
use AiChat\Policies\DefaultReadOnlyPolicy;

class ProjectAssistantAgent extends BaseAgent
{
    protected string $name = 'project_assistant';

    protected string $description = 'AI assistant that answers questions about project data safely';

    protected string $systemPrompt = 'You are a read-only AI assistant designed to answer questions about project data safely and accurately. Never create, update, delete, or modify any data.';

    protected array $tools = [];

    protected array $contextProviders = [];

    protected array $policies = [
        DefaultReadOnlyPolicy::class,
    ];

    protected bool $readOnly = true;
}
PHP;

        File::put($path, $content);
        $this->components->task('Created ProjectAssistantAgent', fn () => true);
    }

    protected function installDefaultPolicy(): void
    {
        $path = app_path('AI/Policies/ProjectChatPolicy.php');

        if (File::exists($path) && ! $this->option('force')) {
            $this->components->twoColumnDetail('ProjectChatPolicy', '<fg=yellow>Already exists</>');

            return;
        }

        $content = <<<'PHP'
<?php

namespace App\AI\Policies;

use AiChat\Contracts\ChatPolicyInterface;
use AiChat\Policies\ChatContext;
use AiChat\Policies\PolicyResult;

class ProjectChatPolicy implements ChatPolicyInterface
{
    protected array $allowedActions = ['read', 'search', 'count', 'stats'];

    protected array $blockedModels = [
        'App\\Models\\PersonalAccessToken',
        'App\\Models\\PasswordResetToken',
    ];

    protected array $blockedFields = [
        'password',
        'remember_token',
        'token',
        'secret',
        'api_key',
    ];

    protected int $maxRecords = 100;

    protected bool $requireAuth = false;

    public function authorize(ChatContext $context): PolicyResult
    {
        if ($this->requireAuth && $context->user === null) {
            return PolicyResult::denied('Authentication is required.', self::class);
        }

        if (! in_array($context->action, $this->allowedActions, true)) {
            return PolicyResult::denied("The action [{$context->action}] is not allowed by this policy.", self::class);
        }

        if (isset($context->payload['model']) && in_array($context->payload['model'], $this->blockedModels, true)) {
            return PolicyResult::denied('Access to this model is blocked by policy.', self::class);
        }

        if (isset($context->payload['fields'])) {
            $blocked = array_intersect($context->payload['fields'], $this->blockedFields);

            if (! empty($blocked)) {
                return PolicyResult::denied('Access to certain fields is blocked by policy.', self::class);
            }
        }

        return PolicyResult::allowed('Action permitted by policy.', self::class);
    }
}
PHP;

        File::put($path, $content);
        $this->components->task('Created ProjectChatPolicy', fn () => true);
    }

    protected function installDefaultKnowledge(): void
    {
        $path = app_path('AI/Knowledge/project-overview.md');

        if (File::exists($path) && ! $this->option('force')) {
            $this->components->twoColumnDetail('project-overview.md', '<fg=yellow>Already exists</>');

            return;
        }

        $content = <<<'MD'
# Project Overview

This document provides a high-level overview of the project for AI context.

## Description

Add your project description here.

## Key Features

- Feature 1
- Feature 2
- Feature 3

## Architecture

- Framework: Laravel
- Database: MySQL/PostgreSQL
- Authentication: Sanctum

## Models

List your important models and their relationships here.
MD;

        File::put($path, $content);
        $this->components->task('Created project-overview.md', fn () => true);
    }

    protected function installPreset(string $preset): void
    {
        if ($preset === 'blank') {
            return;
        }

        $presetPath = __DIR__.'/../../../presets/'.$preset;

        if (! File::isDirectory($presetPath)) {
            $this->components->warn("Preset [{$preset}] files not found. Skipping preset installation.");

            return;
        }

        $this->components->info("Installing [{$preset}] preset...");

        $files = File::allFiles($presetPath);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $destination = app_path('AI/'.$relativePath);

            if (File::exists($destination) && ! $this->option('force')) {
                $this->components->twoColumnDetail($relativePath, '<fg=yellow>Already exists</>');

                continue;
            }

            File::ensureDirectoryExists(dirname($destination));
            File::copy($file->getRealPath(), $destination);
            $this->components->task("Installed {$relativePath}", fn () => true);
        }
    }

    protected function publishWidgetAssets(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'ai-chat-assets',
            '--force' => $this->option('force'),
        ]);
    }

    protected function buildWidget(): void
    {
        if ($this->confirm('Build JavaScript widget?', true)) {
            $this->call('ai-chat:build');
        }
    }

    protected function displayUsage(): void
    {
        $provider = env('AI_CHAT_PROVIDER', config('ai-chat.provider', 'glm'));
        $model = env('AI_CHAT_MODEL', config('ai-chat.model', 'glm-5.1'));
        $providerInfo = ProviderCatalog::providers()[$provider] ?? null;
        $providerName = $providerInfo ? $providerInfo['name'] : $provider;

        $this->newLine();
        $this->components->info("Active Provider: {$providerName} / {$model}");

        if ($providerInfo && $providerInfo['env_key'] && $provider !== 'ollama') {
            $hasKey = env($providerInfo['env_key']) ? true : false;

            if (! $hasKey) {
                $this->components->warn("Add your {$providerName} API key to .env:");
                $this->line("  {$providerInfo['env_key']}=your-api-key-here");
            }
        }

        if ($provider === 'custom') {
            $hasUrl = env('AI_CHAT_CUSTOM_URL') ? true : false;

            if (! $hasUrl) {
                $this->components->warn('Set your custom API URL in .env:');
                $this->line('  AI_CHAT_CUSTOM_URL=http://localhost:11434/v1');
            }
        }

        $this->newLine();
        $this->components->info('Available commands:');
        $this->line('  php artisan ai-chat:scan           - Scan project and generate tools');
        $this->line('  php artisan ai-chat:index-knowledge - Index knowledge documents');
        $this->line('  php artisan ai-chat:make-tool      - Create a custom MCP tool');
        $this->line('  php artisan ai-chat:make-agent     - Create a new AI agent');
        $this->line('  php artisan ai-chat:make-policy    - Create a chat policy');
        $this->line('  php artisan ai-chat:doctor         - Check package health');
        $this->newLine();
        $this->components->info('Widget usage (add to any HTML page):');
        //        $this->line(<<<'HTML'
        // <script src="/vendor/ai-chat/ai-chat-widget.min.js"></script>
        // <script>
        //    AIChatWidget.init({
        //        apiBaseUrl: '/api/ai-chat',
        //        title: 'AI Assistant',
        //        theme: 'light',
        //        position: 'bottom-right',
        //    });
        // </script>
        // HTML);
    }
}
