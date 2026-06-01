<?php

namespace AiChat;

use AiChat\Agents\AgentManager;
use AiChat\Chat\ConversationManager;
use AiChat\Chat\MessageManager;
use AiChat\Contracts\ContextProviderInterface;
use AiChat\MCP\ContextResolver;
use AiChat\MCP\ToolDiscovery;
use AiChat\MCP\ToolRegistry;
use AiChat\Memory\MemoryManager;
use AiChat\Policies\PolicyManager;
use AiChat\RAG\KnowledgeIndexer;
use AiChat\Scanner\ProjectScanner;
use AiChat\Vector\VectorManager;

class AiChatManager
{
    public function __construct(
        protected readonly ToolRegistry $toolRegistry,
        protected readonly AgentManager $agentManager,
        protected readonly PolicyManager $policyManager,
        protected readonly ConversationManager $conversations,
        protected readonly MessageManager $messages,
        protected readonly VectorManager $vectors,
        protected readonly MemoryManager $memory,
        protected readonly KnowledgeIndexer $knowledgeIndexer,
        protected readonly ProjectScanner $projectScanner,
        protected readonly ToolDiscovery $toolDiscovery,
        protected readonly ContextResolver $contextResolver,
    ) {}

    public function toolRegistry(): ToolRegistry
    {
        return $this->toolRegistry;
    }

    public function agentManager(): AgentManager
    {
        return $this->agentManager;
    }

    public function policyManager(): PolicyManager
    {
        return $this->policyManager;
    }

    public function conversations(): ConversationManager
    {
        return $this->conversations;
    }

    public function messages(): MessageManager
    {
        return $this->messages;
    }

    public function vectors(): VectorManager
    {
        return $this->vectors;
    }

    public function memory(): MemoryManager
    {
        return $this->memory;
    }

    public function knowledgeIndexer(): KnowledgeIndexer
    {
        return $this->knowledgeIndexer;
    }

    public function projectScanner(): ProjectScanner
    {
        return $this->projectScanner;
    }

    public function discoverTools(string $path): void
    {
        $classes = $this->toolDiscovery->discoverIn($path);
        foreach ($classes as $class) {
            if (class_exists($class)) {
                $this->toolRegistry->register(app($class));
            }
        }
    }

    public function discoverAgents(string $path): void
    {
        $classes = $this->toolDiscovery->discoverIn($path);
        foreach ($classes as $class) {
            if (class_exists($class) && method_exists($class, 'name')) {
                $agent = app($class);
                $this->agentManager->register($agent);
            }
        }
    }

    /** @var array<string, class-string<ContextProviderInterface>> */
    protected array $contextProviders = [];

    public function discoverContextProviders(string $path): void
    {
        $classes = $this->toolDiscovery->discoverIn($path);

        foreach ($classes as $class) {
            if (class_exists($class) && is_subclass_of($class, ContextProviderInterface::class)) {
                $provider = app($class);
                $this->contextProviders[$provider->name()] = $class;
            }
        }
    }

    public function registeredContextProviders(): array
    {
        return array_keys($this->contextProviders);
    }

    public function contextProviderClasses(): array
    {
        return array_values($this->contextProviders);
    }

    public function registeredTools(): array
    {
        return $this->toolRegistry->names();
    }

    public function registeredAgents(): array
    {
        return array_keys($this->agentManager->all());
    }
}
