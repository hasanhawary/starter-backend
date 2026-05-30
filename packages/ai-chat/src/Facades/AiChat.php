<?php

namespace AiChat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AiChat\MCP\ToolRegistry toolRegistry()
 * @method static \AiChat\Agents\AgentManager agentManager()
 * @method static \AiChat\Policies\PolicyManager policyManager()
 * @method static \AiChat\Chat\ConversationManager conversations()
 * @method static \AiChat\Chat\MessageManager messages()
 * @method static \AiChat\Vector\VectorManager vectors()
 * @method static \AiChat\Memory\MemoryManager memory()
 * @method static \AiChat\RAG\KnowledgeIndexer knowledgeIndexer()
 * @method static \AiChat\Scanner\ProjectScanner projectScanner()
 * @method static void discoverTools(string $path)
 * @method static void discoverAgents(string $path)
 * @method static void discoverContextProviders(string $path)
 * @method static array registeredTools()
 * @method static array registeredAgents()
 */
class AiChat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai-chat';
    }
}
