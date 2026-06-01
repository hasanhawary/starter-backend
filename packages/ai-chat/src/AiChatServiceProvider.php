<?php

namespace AiChat;

use AiChat\Agents\AgentManager;
use AiChat\Agents\AgentResolver;
use AiChat\Agents\ProjectAssistantAgent;
use AiChat\Chat\ChatResponseBuilder;
use AiChat\Chat\ConversationManager;
use AiChat\Chat\HistoryManager;
use AiChat\Chat\HistorySelector;
use AiChat\Chat\MessageManager;
use AiChat\Chat\StreamManager;
use AiChat\Console\Commands\AiChatBuildCommand;
use AiChat\Console\Commands\AiChatInstallCommand;
use AiChat\Console\Commands\DoctorCommand;
use AiChat\Console\Commands\IndexKnowledgeCommand;
use AiChat\Console\Commands\MakeAgentCommand;
use AiChat\Console\Commands\MakePolicyCommand;
use AiChat\Console\Commands\MakeToolCommand;
use AiChat\Console\Commands\ScanProjectCommand;
use AiChat\Contracts\MemoryStoreInterface;
use AiChat\Contracts\VectorStoreInterface;
use AiChat\Generator\AgentGenerator;
use AiChat\Generator\ManifestGenerator;
use AiChat\Generator\PolicyGenerator;
use AiChat\Generator\StubManager;
use AiChat\Generator\ToolGenerator;
use AiChat\Http\Middleware\AiChatRateLimit;
use AiChat\Http\Middleware\ResolveAiChatUser;
use AiChat\MCP\ContextResolver;
use AiChat\MCP\ToolCallLogger;
use AiChat\MCP\ToolDiscovery;
use AiChat\MCP\ToolExecutor;
use AiChat\MCP\ToolInputValidator;
use AiChat\MCP\ToolOutputNormalizer;
use AiChat\MCP\ToolPermissionGuard;
use AiChat\MCP\ToolRegistry;
use AiChat\MCP\ToolSelector;
use AiChat\Memory\MemoryExtractor;
use AiChat\Memory\MemoryManager;
use AiChat\Memory\MemoryRetriever;
use AiChat\Memory\Stores\DatabaseMemoryStore;
use AiChat\Pipeline\ChatPipeline;
use AiChat\Planning\HeuristicPlanner;
use AiChat\Planning\HybridPlanner;
use AiChat\Planning\LlmPlanner;
use AiChat\Planning\ToolSearch\ArrayToolSearchIndex;
use AiChat\Planning\ToolSearch\ToolSearchIndex;
use AiChat\Policies\DefaultReadOnlyPolicy;
use AiChat\Policies\PolicyManager;
use AiChat\Providers\GlmProvider;
use AiChat\RAG\ContextBuilder;
use AiChat\RAG\DocumentChunker;
use AiChat\RAG\DocumentLoader;
use AiChat\RAG\KnowledgeIndexer;
use AiChat\RAG\RetrievalPipeline;
use AiChat\Scanner\ProjectScanner;
use AiChat\Search\HybridSearch;
use AiChat\Search\KnowledgeSearch;
use AiChat\Search\SqlSearch;
use AiChat\Search\VectorSearch;
use AiChat\Storage\AnonymousConversationStore;
use AiChat\Support\TenantResolver;
use AiChat\Support\TokenBudgetManager;
use AiChat\Vector\Drivers\NullVectorDriver;
use AiChat\Vector\Drivers\PgVectorDriver;
use AiChat\Vector\EmbeddingGenerator;
use AiChat\Vector\VectorManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Gateway\OpenAi\OpenAiGateway;
use Laravel\Ai\Providers\AnthropicProvider;
use Laravel\Ai\Providers\AzureOpenAiProvider;
use Laravel\Ai\Providers\BedrockProvider;
use Laravel\Ai\Providers\DeepSeekProvider;
use Laravel\Ai\Providers\GeminiProvider;
use Laravel\Ai\Providers\GroqProvider;
use Laravel\Ai\Providers\MistralProvider;
use Laravel\Ai\Providers\OllamaProvider;
use Laravel\Ai\Providers\OpenAiProvider;
use Laravel\Ai\Providers\OpenRouterProvider;
use Laravel\Ai\Providers\XaiProvider;

class AiChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-chat.php', 'ai-chat');
        $this->mergeConfigFrom(__DIR__.'/../config/ai-chat-dialects.php', 'ai-chat-dialects');

        $this->registerCoreBindings();
        $this->registerMcpBindings();
        $this->registerChatBindings();
        $this->registerRagBindings();
        $this->registerVectorBindings();
        $this->registerMemoryBindings();
        $this->registerSearchBindings();
        $this->registerScannerBindings();
        $this->registerGeneratorBindings();
        $this->registerPipelineBindings();
        $this->registerFacades();
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerCommands();
        $this->registerMigrations();
        $this->registerPublishables();
        $this->registerGlmDriver();
        $this->registerBladeDirectives();
        $this->autoDiscoverProjectFiles();
    }

    protected function registerCoreBindings(): void
    {
        $this->app->bind(ConversationStore::class, AnonymousConversationStore::class);
        $this->app->singleton(AnonymousConversationStore::class);
        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(AgentManager::class);
        $this->app->singleton(PolicyManager::class);
        $this->app->singleton(TenantResolver::class);
    }

    protected function registerMcpBindings(): void
    {
        $this->app->singleton(ToolDiscovery::class);
        $this->app->singleton(ToolExecutor::class);
        $this->app->singleton(ToolPermissionGuard::class);
        $this->app->singleton(ToolInputValidator::class);
        $this->app->singleton(ToolOutputNormalizer::class);
        $this->app->singleton(ToolCallLogger::class);
        $this->app->singleton(ContextResolver::class);
    }

    protected function registerChatBindings(): void
    {
        $this->app->singleton(ConversationManager::class);
        $this->app->singleton(MessageManager::class);
        $this->app->singleton(HistoryManager::class);
        $this->app->singleton(HistorySelector::class);
        $this->app->singleton(StreamManager::class);
        $this->app->singleton(ChatResponseBuilder::class);
    }

    protected function registerRagBindings(): void
    {
        $this->app->singleton(DocumentLoader::class);
        $this->app->singleton(DocumentChunker::class);
        $this->app->singleton(KnowledgeIndexer::class);
        $this->app->singleton(RetrievalPipeline::class);
        $this->app->singleton(ContextBuilder::class);
    }

    protected function registerVectorBindings(): void
    {
        $this->app->singleton(VectorStoreInterface::class, function ($app) {
            $driver = config('ai-chat.vector.driver', 'null');

            return match ($driver) {
                'database' => new Drivers\DatabaseVectorDriver([
                    'connection' => config('ai-chat.vector.database.connection', config('database.default')),
                    'table' => config('ai-chat.vector.database.table', 'ai_knowledge_chunks'),
                ]),
                'pgvector' => new PgVectorDriver([
                    'connection' => config('ai-chat.vector.pgvector.connection', 'pgsql'),
                    'table' => config('ai-chat.vector.pgvector.table', 'ai_vectors'),
                ]),
                'qdrant' => new Drivers\QdrantDriver([
                    'url' => config('ai-chat.vector.qdrant.url', 'http://localhost:6333'),
                    'api_key' => config('ai-chat.vector.qdrant.api_key'),
                    'collection' => config('ai-chat.vector.qdrant.collection', 'ai_chat'),
                ]),
                'pinecone' => new Drivers\PineconeDriver([
                    'api_key' => config('ai-chat.vector.pinecone.api_key'),
                    'environment' => config('ai-chat.vector.pinecone.environment'),
                    'index' => config('ai-chat.vector.pinecone.index', 'ai-chat'),
                ]),
                default => new NullVectorDriver,
            };
        });

        $this->app->singleton(EmbeddingGenerator::class);
        $this->app->singleton(VectorManager::class);
    }

    protected function registerMemoryBindings(): void
    {
        $this->app->singleton(MemoryStoreInterface::class, function ($app) {
            $storeClass = config('ai-chat.memory.store');

            if ($storeClass && class_exists($storeClass)) {
                return $app->make($storeClass);
            }

            if (config('ai-chat.memory.enabled', false)) {
                return $app->make(DatabaseMemoryStore::class);
            }

            return new class implements MemoryStoreInterface
            {
                public function store(string $conversationId, string $content, array $embedding): bool
                {
                    return true;
                }

                public function retrieve(string $conversationId, array $embedding, int $limit = 5): array
                {
                    return [];
                }

                public function forget(string $conversationId): bool
                {
                    return true;
                }
            };
        });

        $this->app->singleton(MemoryManager::class);
        $this->app->singleton(MemoryRetriever::class);
    }

    protected function registerSearchBindings(): void
    {
        $this->app->singleton(SqlSearch::class);
        $this->app->singleton(VectorSearch::class);
        $this->app->singleton(KnowledgeSearch::class);
        $this->app->singleton(HybridSearch::class);
    }

    protected function registerScannerBindings(): void
    {
        $this->app->singleton(ProjectScanner::class);
        $this->app->singleton(Scanner\ModelScanner::class);
        $this->app->singleton(Scanner\RelationScanner::class);
        $this->app->singleton(Scanner\RouteScanner::class);
        $this->app->singleton(Scanner\ControllerScanner::class);
        $this->app->singleton(Scanner\ServiceScanner::class);
        $this->app->singleton(Scanner\PolicyScanner::class);
        $this->app->singleton(Scanner\MigrationScanner::class);
        $this->app->singleton(Scanner\ProjectMapBuilder::class);
    }

    protected function registerGeneratorBindings(): void
    {
        $this->app->singleton(StubManager::class);
        $this->app->singleton(ToolGenerator::class);
        $this->app->singleton(AgentGenerator::class);
        $this->app->singleton(PolicyGenerator::class);
        $this->app->singleton(ManifestGenerator::class);
    }

    protected function registerPipelineBindings(): void
    {
        $this->app->singleton(ChatPipeline::class);
        $this->app->singleton(AgentResolver::class);
        $this->app->singleton(HeuristicPlanner::class);
        $this->app->singleton(LlmPlanner::class);
        $this->app->singleton(HybridPlanner::class);
        $this->app->singleton(ToolSelector::class);
        $this->app->singleton(TokenBudgetManager::class);
        $this->app->singleton(MemoryExtractor::class);
        $this->app->bind(ToolSearchIndex::class, function ($app) {
            $driver = config('ai-chat.planning.tool_search.driver', 'array');

            return match ($driver) {
                'array' => new ArrayToolSearchIndex,
                default => new ArrayToolSearchIndex,
            };
        });
    }

    protected function registerFacades(): void
    {
        $this->app->singleton('ai-chat', AiChatManager::class);
        $this->app->singleton('ai-chat.mcp', ToolRegistry::class);
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-chat.php', 'ai-chat');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-chat');
    }

    protected function registerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/api.php');
    }

    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('ai-chat.rate-limit', AiChatRateLimit::class);
        $this->app['router']->aliasMiddleware('ai-chat.resolve-user', ResolveAiChatUser::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AiChatInstallCommand::class,
                AiChatBuildCommand::class,
                ScanProjectCommand::class,
                IndexKnowledgeCommand::class,
                MakeToolCommand::class,
                MakeAgentCommand::class,
                MakePolicyCommand::class,
                DoctorCommand::class,
            ]);
        }
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ai-chat'),
        ], 'ai-chat-assets');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'ai-chat-migrations');

        $this->publishes([
            __DIR__.'/../stubs' => resource_path('stubs/ai-chat'),
        ], 'ai-chat-stubs');
    }

    protected function registerGlmDriver(): void
    {
        if (! class_exists(AiManager::class)) {
            return;
        }

        $this->app->extend(AiManager::class, function (AiManager $manager) {
            $manager->extend('glm', function ($app, array $config) {
                return new GlmProvider($config, $app->make('events'));
            });

            $manager->extend('custom', function ($app, array $config) {
                return new OpenAiProvider(array_merge($config, [
                    'driver' => 'openai',
                    'url' => $config['url'] ?? env('AI_CHAT_CUSTOM_URL'),
                    'key' => $config['key'] ?? env('AI_CHAT_CUSTOM_KEY', ''),
                ]), $app->make('events'));
            });

            $manager->extend('deepseek', function ($app, array $config) {
                return new DeepSeekProvider($config, $app->make('events'));
            });

            $manager->extend('groq', function ($app, array $config) {
                return new GroqProvider($config, $app->make('events'));
            });

            $manager->extend('mistral', function ($app, array $config) {
                return new MistralProvider($config, $app->make('events'));
            });

            $manager->extend('xai', function ($app, array $config) {
                return new XaiProvider($config, $app->make('events'));
            });

            $manager->extend('anthropic', function ($app, array $config) {
                return new AnthropicProvider($config, $app->make('events'));
            });

            $manager->extend('gemini', function ($app, array $config) {
                return new GeminiProvider($config, $app->make('events'));
            });

            $manager->extend('openai', function ($app, array $config) {
                return new OpenAiProvider(new OpenAiGateway($app['events']), $config, $app->make('events'));
            });

            $manager->extend('openrouter', function ($app, array $config) {
                return new OpenRouterProvider($config, $app->make('events'));
            });

            $manager->extend('ollama', function ($app, array $config) {
                return new OllamaProvider($config, $app->make('events'));
            });

            $manager->extend('azure_openai', function ($app, array $config) {
                return new AzureOpenAiProvider($config, $app->make('events'));
            });

            $manager->extend('bedrock', function ($app, array $config) {
                return new BedrockProvider($config, $app->make('events'));
            });

            return $manager;
        });
    }

    protected function registerBladeDirectives(): void
    {
        $this->app->booted(function () {
            $blade = $this->app['blade.compiler'];
            $blade->directive('aiChat', function () {
                if (config('ai-chat.widget.enabled', true)) {
                    return "<?php echo view('ai-chat::widget'); ?>";
                }

                return '';
            });
        });
    }

    protected function autoDiscoverProjectFiles(): void
    {
        $this->app->booted(function () {
            if (! config('ai-chat.auto_discovery.tools', true)) {
                return;
            }

            $manager = $this->app->make(AiChatManager::class);

            foreach (config('ai-chat.discovery_paths', []) as $type => $path) {
                if (! is_dir($path)) {
                    continue;
                }

                match ($type) {
                    'tools' => $manager->discoverTools($path),
                    'agents' => $manager->discoverAgents($path),
                    'context_providers' => $manager->discoverContextProviders($path),
                    default => null,
                };
            }

            if (! $manager->agentManager()->has('project_assistant')) {
                $manager->agentManager()->register(new ProjectAssistantAgent);
            }

            $manager->policyManager()->register(new DefaultReadOnlyPolicy);

            if (config('ai-chat.tenant_resolver')) {
                TenantResolver::setResolver(config('ai-chat.tenant_resolver'));
            }
        });
    }
}
