---
inclusion: manual
---

# AI Chat Package Roadmap

## Architecture

The package lives at `packages/ai-chat/` and follows a **12-step pipeline pattern**:

```
ValidateMessage → ResolveUser → ResolveAgent → PlanStep → ApplyPolicies
→ ResolveContext → RetrieveKnowledge → RetrieveMemory → ResolveTools
→ SendToProvider → ExecuteTools → PersistResponse
```

---

## What's Fully Implemented ✅

### Core Chat
- Sync + streaming (SSE) message handling via `AiChatController`
- Conversation/message persistence with full CRUD
- Anonymous sessions (via `session_id`) + Sanctum-authenticated users
- Rate limiting middleware

### Tool System (MCP)
- `ToolRegistry`, `ToolDiscovery`, `ToolExecutor`, `ToolSelector`, `ToolAdapter`
- 34 auto-generated tools in `app/AI/Tools/Generated/` covering `Country`, `Notification`, `Permission`, `Role`, `Setting`, `User`, `UserSetting` models (Count, LatestRecords, Search, Stats, Summary variants)
- Tool call logging via `AiToolCall` model

### Planning
- `HeuristicPlanner` — pattern-matching with Arabic/English dialect support (`config/ai-chat-dialects.php`)
- `LlmPlanner` — LLM-based for complex queries
- `HybridPlanner` — heuristic first, LLM fallback
- `ExecutionPlan` carries intent, tools, RAG/memory flags, history mode

### Memory
- `MemoryExtractor` — extracts facts (names, preferences) from exchanges
- `DatabaseMemoryStore` — embedding-based retrieval
- `AiMemory` model + migration

### RAG / Knowledge
- `KnowledgeIndexer`, `DocumentLoader`, `DocumentChunker`
- `RetrievalPipeline`, `ContextBuilder`
- `HybridSearch`, `VectorSearch`, `SqlSearch`
- 4 vector store drivers: database (default), pgvector, Qdrant, Pinecone

### Project Scanner
- `ProjectScanner` with sub-scanners for models, routes, controllers, services, policies, migrations
- `AiProjectMap` model + `ai-chat:scan` command

### Providers
- 15+ LLM providers registered: OpenAI, Anthropic, Gemini, DeepSeek, Groq, Mistral, xAI, GLM, OpenRouter, Ollama, Azure, Bedrock, Cohere, Together, custom

### Widget
- `@aiChat` Blade directive → `resources/views/widget.blade.php`
- Fully configurable (position, theme, colors, suggested prompts, etc.)

### Artisan Commands
- `ai-chat:install`, `ai-chat:build`, `ai-chat:scan`, `ai-chat:index-knowledge`
- `make:ai-tool`, `make:ai-agent`, `make:ai-policy`
- `ai-chat:doctor` (health check)

### Tests
- Package-level: planning, memory, pipeline, vector, response formatter, tool selector
- App-level: `SendMessageTest`, `ChatWidgetTest`, `StreamLeakTest`, knowledge/memory integration tests

---

## Known Issues / Incomplete Areas ⚠️

1. **Missing `use` import in all `*CountTool` files** — `SafeQueryBuilder::isSafeColumn()` is called without importing `AiChat\Support\SafeQueryBuilder`. Fatal error at runtime when filters are applied.

2. **`discoverContextProviders()` is a no-op** — `AiChatManager::discoverContextProviders()` has an empty body. Context provider auto-discovery is not wired up despite the interface and `ContextResolver` existing.

3. **`BuildPrompt` pipeline step is unused** — Fully implemented but not in `ChatPipeline::$steps`. Appears to be a planned replacement for the inline prompt building in `AiChatController::buildSystemPrompt()`.

4. **Dead code: `ChatController`** — A parallel controller exists alongside `AiChatController` but routes only register the latter. It's either an older version or a simpler alternative that was abandoned.

5. **`ValidateAIToken` middleware not applied to AI chat routes** — Exists in `app/Http/Middleware/` but the AI chat routes only use `ai-chat.rate-limit` and `ai-chat.resolve-user`.

6. **Memory config fallback mismatch** — `config('ai-chat.memory.enabled', false)` uses `false` as fallback in `PersistResponse`, but the config file defaults to `true`. Memory extraction only runs if `.env` explicitly sets `AI_CHAT_MEMORY_ENABLED=true`.

7. **`User` model missing `Stats` and `Summary` tools** — Other models have all 5 tool variants; `User` appears to be missing `UserStatsTool` and `UserSummaryTool`.

---

## Integration Points

- **Auth**: Routes are public by default — no auth required, just a `session_id`. Sanctum is optional.
- **LLM calls**: Delegates to `laravel/ai` (`AiManager`), extended with custom drivers.
- **Streaming**: SSE via `StreamedResponse`, not WebSockets/Reverb.
- **No hard FK to `users`**: Conversations link via `session_id`; `user_id` on usage/memory tables is nullable.

---

## Key File Locations

| Area | Path |
|------|------|
| Service Provider | `packages/ai-chat/src/AiChatServiceProvider.php` |
| Main Manager | `packages/ai-chat/src/AiChatManager.php` |
| Pipeline | `packages/ai-chat/src/Pipeline/ChatPipeline.php` |
| Controller | `packages/ai-chat/src/Http/Controllers/AiChatController.php` |
| Chat Agent | `packages/ai-chat/src/Agents/ChatAgent.php` |
| Config | `packages/ai-chat/config/ai-chat.php` |
| Routes | `packages/ai-chat/routes/api.php` |
| App Agent | `app/AI/Agents/ProjectAssistantAgent.php` |
| App Policy | `app/AI/Policies/ProjectChatPolicy.php` |
| Generated Tools | `app/AI/Tools/Generated/` |
| App Tests | `tests/Feature/AiChat/` |
