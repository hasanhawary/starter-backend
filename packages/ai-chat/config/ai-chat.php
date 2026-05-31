<?php

return [

    'provider' => env('AI_CHAT_PROVIDER', 'openai'),

    'model' => env('AI_CHAT_MODEL', 'gpt-5.4'),

    'thinking' => [
        'enabled' => env('AI_CHAT_THINKING_ENABLED', false),
        'type' => env('AI_CHAT_THINKING_TYPE', 'enabled'),
    ],

    'max_tokens' => (int) env('AI_CHAT_MAX_TOKENS', 65536),

    'temperature' => (float) env('AI_CHAT_TEMPERATURE', 1.0),

    'timeout' => (int) env('AI_CHAT_TIMEOUT', 300),

    'read_only' => env('AI_CHAT_READ_ONLY', true),

    'default_agent' => env('AI_CHAT_DEFAULT_AGENT', 'project_assistant'),

    'allowed_actions' => ['read', 'summary', 'analytics', 'count', 'search', 'explain'],

    'blocked_actions' => ['create', 'update', 'delete', 'export_sensitive', 'charge', 'refund', 'send', 'execute'],

    'blocked_models' => [
        'App\Models\PasswordResetToken',
        'App\Models\PersonalAccessToken',
    ],

    'blocked_fields' => [
        'password', 'remember_token', 'token', 'secret', 'api_key',
        'access_token', 'refresh_token', 'credit_card', 'card_number',
        'cvv', 'private_key', 'otp_data', 'two_factor_secret',
    ],

    'allowed_models' => [],

    'rate_limiting' => [
        'enabled' => env('AI_CHAT_RATE_LIMIT_ENABLED', true),
        'max_requests' => (int) env('AI_CHAT_RATE_LIMIT_MAX', 50),
        'decay_minutes' => (int) env('AI_CHAT_RATE_LIMIT_DECAY', 60),
    ],

    'conversations' => [
        'max_messages' => (int) env('AI_CHAT_MAX_MESSAGES', 100),
        'history_limit' => (int) env('AI_CHAT_HISTORY_LIMIT', 6),
        'max_prompt_tokens' => (int) env('AI_CHAT_MAX_PROMPT_TOKENS', 4000),
        'default_system_prompt' => env('AI_CHAT_SYSTEM_PROMPT', 'You are an AI assistant embedded inside a Laravel admin dashboard. Answer like a practical product assistant. Be concise, direct, and helpful. Use the same language as the user. Do not mention internal tool names unless the user asks for technical details. Do not provide long generic disclaimers. If required app data is unavailable, say so briefly. Never guess live app data.'),
    ],

    'tool_logging' => [
        'driver' => env('AI_CHAT_TOOL_LOG_DRIVER', 'database'),
    ],

    'policies' => [
        'read_only' => env('AI_CHAT_READ_ONLY', true),
        'blocked_actions' => ['create', 'update', 'delete', 'export_sensitive', 'charge', 'refund', 'send', 'execute'],
        'write_actions' => ['create', 'update', 'delete', 'write', 'modify', 'execute'],
    ],

    'widget' => [
        'enabled' => env('AI_CHAT_WIDGET_ENABLED', true),
        'auto_inject' => env('AI_CHAT_WIDGET_AUTO_INJECT', false),
        'allowed_origins' => env('AI_CHAT_ALLOWED_ORIGINS', '*'),
        'theme' => env('AI_CHAT_WIDGET_THEME', 'light'),
        'position' => env('AI_CHAT_WIDGET_POSITION', 'bottom-right'),
        'title' => env('AI_CHAT_WIDGET_TITLE', 'AI Assistant'),
        'subtitle' => env('AI_CHAT_WIDGET_SUBTITLE', 'Ask me anything'),
        'primary_color' => env('AI_CHAT_WIDGET_PRIMARY_COLOR', '#6366f1'),
        'avatar_url' => env('AI_CHAT_WIDGET_AVATAR_URL', ''),
        'welcome_message' => env('AI_CHAT_WIDGET_WELCOME_MESSAGE', 'Hello! How can I help you today?'),
        'allow_fullscreen' => env('AI_CHAT_WIDGET_ALLOW_FULLSCREEN', true),
        'auto_open' => env('AI_CHAT_WIDGET_AUTO_OPEN', false),
        'open_delay' => (int) env('AI_CHAT_WIDGET_OPEN_DELAY', 3000),
        'height' => (int) env('AI_CHAT_WIDGET_HEIGHT', 600),
        'width' => (int) env('AI_CHAT_WIDGET_WIDTH', 380),
        'suggested_prompts' => [
            env('AI_CHAT_PROMPT_1', 'What can you help me with?'),
            env('AI_CHAT_PROMPT_2', 'Summarize the project'),
            env('AI_CHAT_PROMPT_3', 'List available models'),
            env('AI_CHAT_PROMPT_4', 'Check system status'),
        ],
        'show_branding' => true,
        'show_feedback' => true,
        'show_suggestions' => true,
        'agent_avatar' => env('AI_CHAT_WIDGET_AVATAR_URL', ''),
        'online_status' => 'online',
    ],

    'knowledge' => [
        'enabled' => env('AI_CHAT_KNOWLEDGE_ENABLED', true),
        'paths' => [
            app_path('AI/Knowledge'),
            base_path('README.md'),
            base_path('docs'),
        ],
        'chunk_size' => (int) env('AI_CHAT_CHUNK_SIZE', 500),
        'chunk_overlap' => (int) env('AI_CHAT_CHUNK_OVERLAP', 50),
        'vector_store' => env('AI_CHAT_KNOWLEDGE_VECTOR_STORE'),
        'token_budget' => (int) env('AI_CHAT_KNOWLEDGE_TOKEN_BUDGET', 2000),
    ],

    'vector' => [
        'driver' => env('AI_CHAT_VECTOR_DRIVER', 'database'),
        'database' => [
            'connection' => env('AI_CHAT_VECTOR_DB_CONNECTION'),
            'table' => env('AI_CHAT_VECTOR_DB_TABLE', 'ai_knowledge_chunks'),
        ],
        'pgvector' => [
            'connection' => env('AI_CHAT_PGVECTOR_CONNECTION', 'pgsql'),
            'table' => env('AI_CHAT_PGVECTOR_TABLE', 'ai_vectors'),
        ],
        'qdrant' => [
            'url' => env('AI_CHAT_QDRANT_URL', 'http://localhost:6333'),
            'api_key' => env('AI_CHAT_QDRANT_API_KEY'),
            'collection' => env('AI_CHAT_QDRANT_COLLECTION', 'ai_chat'),
        ],
        'pinecone' => [
            'api_key' => env('AI_CHAT_PINECONE_API_KEY'),
            'environment' => env('AI_CHAT_PINECONE_ENVIRONMENT'),
            'index' => env('AI_CHAT_PINECONE_INDEX', 'ai-chat'),
        ],
    ],

    'memory' => [
        'enabled' => env('AI_CHAT_MEMORY_ENABLED', true),
        'store_every_message' => env('AI_CHAT_STORE_EVERY_MESSAGE', true),
        'extract_after_messages' => (int) env('AI_CHAT_EXTRACT_AFTER_MESSAGES', 6),
        'min_importance' => (float) env('AI_CHAT_MIN_IMPORTANCE', 0.6),
        'token_budget' => (int) env('AI_CHAT_MEMORY_TOKEN_BUDGET', 10000),
    ],

    'planning' => [
        'mode' => env('AI_CHAT_PLANNING_MODE', 'hybrid'), // Options: hybrid, llm, heuristic
        'use_llm_planner_for_complex_questions_only' => env('AI_CHAT_USE_LLM_PLANNER_COMPLEX_ONLY', true),
        'planner_model' => env('AI_CHAT_PLANNER_MODEL'),
        'cache_plans' => env('AI_CHAT_CACHE_PLANS', true),
        'cache_ttl' => (int) env('AI_CHAT_PLAN_CACHE_TTL', 3600),
        'heuristic_confidence_threshold' => (float) env('AI_CHAT_HEURISTIC_CONFIDENCE_THRESHOLD', 0.75),
        'tool_match_threshold' => (float) env('AI_CHAT_TOOL_MATCH_THRESHOLD', 0.70),
        'tool_search' => [
            'driver' => env('AI_CHAT_TOOL_SEARCH_DRIVER', 'array'),
            'drivers' => [
                'array' => [],
                'meilisearch' => [
                    'enabled' => false,
                    'index' => 'ai_chat_tools',
                ],
                'typesense' => [
                    'enabled' => false,
                    'collection' => 'ai_chat_tools',
                ],
                'opensearch' => [
                    'enabled' => false,
                    'index' => 'ai_chat_tools',
                ],
                'elasticsearch' => [
                    'enabled' => false,
                    'index' => 'ai_chat_tools',
                ],
            ],
        ],
    ],

    'context' => [
        'max_context_tokens' => (int) env('AI_CHAT_MAX_CONTEXT_TOKENS', 8000),
        'system_prompt_budget' => (int) env('AI_CHAT_SYSTEM_PROMPT_BUDGET', 1500),
        'max_tools' => (int) env('AI_CHAT_MAX_TOOLS', 5),
        'rag_limit' => (int) env('AI_CHAT_RAG_LIMIT', 3),
        'memory_limit' => (int) env('AI_CHAT_MEMORY_LIMIT', 3),
        'history_limit' => (int) env('AI_CHAT_HISTORY_LIMIT', 6),
        'max_tool_result_items' => (int) env('AI_CHAT_MAX_TOOL_RESULT_ITEMS', 10),
    ],

    'tenant_resolver' => env('AI_CHAT_TENANT_RESOLVER'),

    'auto_discovery' => [
        'tools' => env('AI_CHAT_AUTO_DISCOVER_TOOLS', true),
        'agents' => env('AI_CHAT_AUTO_DISCOVER_AGENTS', true),
        'policies' => env('AI_CHAT_AUTO_DISCOVER_POLICIES', true),
        'context_providers' => env('AI_CHAT_AUTO_DISCOVER_CONTEXT', true),
    ],

    'discovery_paths' => [
        'tools' => app_path('AI/Tools'),
        'agents' => app_path('AI/Agents'),
        'policies' => app_path('AI/Policies'),
        'context_providers' => app_path('AI/ContextProviders'),
    ],

    'analytics' => [
        'max_records' => env('AI_CHAT_ANALYTICS_MAX_RECORDS', 100),
    ],

    'project_map' => [
        'storage_path' => storage_path('ai/project-map.json'),
    ],

    'default' => env('AI_CHAT_PROVIDER', 'openai'),
    'default_for_images' => 'gemini',
    'default_for_audio' => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings' => 'openai',
    'default_for_reranking' => 'cohere',

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_OPENAI_TEXT_MODEL', 'gpt-5.4'),
                    'cheapest' => 'gpt-5.4-nano',
                    'smartest' => 'gpt-5.5',
                ],
                'embeddings' => [
                    'default' => 'text-embedding-3-small',
                    'dimensions' => 1536,
                ],
            ],
        ],
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_ANTHROPIC_TEXT_MODEL', 'claude-sonnet-4-6'),
                    'cheapest' => 'claude-haiku-4-5-20251001',
                    'smartest' => 'claude-opus-4-8',
                ],
            ],
        ],
        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_GEMINI_TEXT_MODEL', 'gemini-3-flash-preview'),
                    'cheapest' => 'gemini-3.1-flash-lite-preview',
                    'smartest' => 'gemini-3.1-pro-preview',
                ],
                'embeddings' => [
                    'default' => 'gemini-embedding-001',
                    'dimensions' => 3072,
                ],
            ],
        ],
        'deepseek' => [
            'driver' => 'deepseek',
            'key' => env('DEEPSEEK_API_KEY'),
            'url' => env('DEEPSEEK_URL', 'https://api.deepseek.com'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_DEEPSEEK_TEXT_MODEL', 'deepseek-v4-flash'),
                    'cheapest' => 'deepseek-v4-flash',
                    'smartest' => 'deepseek-v4-pro',
                ],
            ],
        ],
        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
            'url' => env('GROQ_URL', 'https://api.groq.com/openai/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_GROQ_TEXT_MODEL', 'llama-3.3-70b-versatile'),
                    'cheapest' => 'llama-3.1-8b-instant',
                    'smartest' => 'openai/gpt-oss-120b',
                ],
            ],
        ],
        'mistral' => [
            'driver' => 'mistral',
            'key' => env('MISTRAL_API_KEY'),
            'url' => env('MISTRAL_URL', 'https://api.mistral.ai/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_MISTRAL_TEXT_MODEL', 'mistral-large-latest'),
                    'cheapest' => 'mistral-small-latest',
                    'smartest' => 'mistral-large-latest',
                ],
                'embeddings' => [
                    'default' => 'mistral-embed',
                    'dimensions' => 1024,
                ],
            ],
        ],
        'xai' => [
            'driver' => 'xai',
            'key' => env('XAI_API_KEY'),
            'url' => env('XAI_URL', 'https://api.x.ai/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_XAI_TEXT_MODEL', 'grok-4.3'),
                    'cheapest' => 'grok-4.3',
                    'smartest' => 'grok-4.3',
                ],
            ],
        ],
        'openrouter' => [
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
            'url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_OPENROUTER_TEXT_MODEL', 'openrouter/free'),
                    'cheapest' => 'openrouter/free',
                    'smartest' => 'openrouter/free',
                ],
            ],
        ],
        'glm' => [
            'driver' => 'glm',
            'key' => env('GLM_API_KEY'),
            'url' => env('GLM_API_URL', 'https://open.bigmodel.cn/api/paas/v4'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_GLM_TEXT_MODEL', 'glm-5.1'),
                    'cheapest' => 'glm-4.5-flash',
                    'smartest' => 'glm-5.1',
                ],
            ],
        ],
        'azure_openai' => [
            'driver' => 'azure_openai',
            'key' => env('AZURE_OPENAI_API_KEY'),
            'url' => env('AZURE_OPENAI_URL'),
            'deployment' => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-5.4'),
            'api_version' => env('AZURE_OPENAI_API_VERSION', '2025-04-01-preview'),
            'models' => [
                'text' => [
                    'default' => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-5.4'),
                ],
                'embeddings' => [
                    'default' => 'text-embedding-3-small',
                    'dimensions' => 1536,
                ],
            ],
        ],
        'bedrock' => [
            'driver' => 'bedrock',
            'access_key_id' => env('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
            'session_token' => env('AWS_SESSION_TOKEN'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_BEDROCK_TEXT_MODEL', 'us.anthropic.claude-sonnet-4-6-v1:0'),
                    'cheapest' => 'us.anthropic.claude-haiku-4-5-20251001-v1:0',
                    'smartest' => 'us.anthropic.claude-opus-4-6-v1',
                ],
                'embeddings' => [
                    'default' => 'amazon.titan-embed-text-v2:0',
                    'dimensions' => 1024,
                ],
            ],
        ],
        'cohere' => [
            'driver' => 'cohere',
            'key' => env('COHERE_API_KEY'),
            'url' => env('COHERE_URL', 'https://api.cohere.com/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_COHERE_TEXT_MODEL', 'command-a-plus-05-2026'),
                    'cheapest' => 'command-r7b-12-2024',
                    'smartest' => 'command-a-plus-05-2026',
                ],
                'embeddings' => [
                    'default' => 'embed-english-v3.0',
                    'dimensions' => 1024,
                ],
            ],
        ],
        'together' => [
            'driver' => 'openai',
            'key' => env('TOGETHER_API_KEY'),
            'url' => env('TOGETHER_URL', 'https://api.together.ai/v1'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_TOGETHER_TEXT_MODEL', 'deepseek-ai/DeepSeek-V4-Flash'),
                    'cheapest' => 'mistralai/Ministral-3-14B-Instruct-2512',
                    'smartest' => 'deepseek-ai/DeepSeek-V4-Pro',
                ],
            ],
        ],
        'ollama' => [
            'driver' => 'ollama',
            'key' => env('OLLAMA_API_KEY', ''),
            'url' => env('OLLAMA_URL', 'http://localhost:11434'),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_OLLAMA_TEXT_MODEL', 'llama3.1:8b'),
                    'cheapest' => 'llama3.1:8b',
                    'smartest' => 'llama3.1:70b',
                ],
                'embeddings' => [
                    'default' => 'nomic-embed-text',
                    'dimensions' => 768,
                ],
            ],
        ],
        'custom' => [
            'driver' => env('AI_CHAT_CUSTOM_DRIVER', 'openai'),
            'key' => env('AI_CHAT_CUSTOM_KEY', ''),
            'url' => env('AI_CHAT_CUSTOM_URL', ''),
            'models' => [
                'text' => [
                    'default' => env('AI_CHAT_MODEL', ''),
                ],
            ],
        ],
    ],
];
