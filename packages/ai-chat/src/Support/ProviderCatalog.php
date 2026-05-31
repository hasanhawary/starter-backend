<?php

namespace AiChat\Support;

class ProviderCatalog
{
    public static function providers(): array
    {
        return [
            'openai' => [
                'name' => 'OpenAI',
                'driver' => 'openai',
                'env_key' => 'OPENAI_API_KEY',
                'env_url' => 'OPENAI_URL',
                'default_url' => 'https://api.openai.com/v1',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => true,
            ],
            'anthropic' => [
                'name' => 'Anthropic Claude',
                'driver' => 'anthropic',
                'env_key' => 'ANTHROPIC_API_KEY',
                'env_url' => 'ANTHROPIC_URL',
                'default_url' => 'https://api.anthropic.com/v1',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => true,
                'supports_audio' => false,
            ],
            'gemini' => [
                'name' => 'Google Gemini',
                'driver' => 'gemini',
                'env_key' => 'GEMINI_API_KEY',
                'env_url' => 'GEMINI_URL',
                'default_url' => 'https://generativelanguage.googleapis.com/v1beta/',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => true,
            ],
            'deepseek' => [
                'name' => 'DeepSeek',
                'driver' => 'deepseek',
                'env_key' => 'DEEPSEEK_API_KEY',
                'env_url' => 'DEEPSEEK_URL',
                'default_url' => 'https://api.deepseek.com',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => false,
                'supports_audio' => false,
                'note' => 'V4 models (deepseek-v4-pro, deepseek-v4-flash) now available. Legacy deepseek-chat and deepseek-reasoner retire July 24, 2026.',
            ],
            'groq' => [
                'name' => 'Groq',
                'driver' => 'groq',
                'env_key' => 'GROQ_API_KEY',
                'env_url' => 'GROQ_URL',
                'default_url' => 'https://api.groq.com/openai/v1',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'mistral' => [
                'name' => 'Mistral AI',
                'driver' => 'mistral',
                'env_key' => 'MISTRAL_API_KEY',
                'env_url' => 'MISTRAL_URL',
                'default_url' => 'https://api.mistral.ai/v1',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'xai' => [
                'name' => 'xAI Grok',
                'driver' => 'xai',
                'env_key' => 'XAI_API_KEY',
                'env_url' => 'XAI_URL',
                'default_url' => 'https://api.x.ai/v1',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => true,
                'supports_audio' => false,
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'driver' => 'openrouter',
                'env_key' => 'OPENROUTER_API_KEY',
                'env_url' => 'OPENROUTER_BASE_URL',
                'default_url' => 'https://openrouter.ai/api/v1',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => true,
            ],
            'glm' => [
                'name' => 'Z.ai / GLM',
                'driver' => 'glm',
                'env_key' => 'GLM_API_KEY',
                'alt_env_key' => 'ZAI_API_KEY',
                'env_url' => 'GLM_API_URL',
                'default_url' => 'https://open.bigmodel.cn/api/paas/v4',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'azure_openai' => [
                'name' => 'Azure OpenAI',
                'driver' => 'azure_openai',
                'env_key' => 'AZURE_OPENAI_API_KEY',
                'env_url' => 'AZURE_OPENAI_URL',
                'default_url' => null,
                'category' => 'enterprise',
                'requires_key' => true,
                'requires_url' => true,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => false,
            ],
            'bedrock' => [
                'name' => 'AWS Bedrock',
                'driver' => 'bedrock',
                'env_key' => 'AWS_ACCESS_KEY_ID',
                'env_url' => null,
                'default_url' => null,
                'category' => 'enterprise',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => false,
            ],
            'ollama' => [
                'name' => 'Ollama (Local)',
                'driver' => 'ollama',
                'env_key' => null,
                'env_url' => 'OLLAMA_URL',
                'default_url' => 'http://localhost:11434',
                'category' => 'local',
                'requires_key' => false,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'cohere' => [
                'name' => 'Cohere',
                'driver' => 'cohere',
                'env_key' => 'COHERE_API_KEY',
                'env_url' => 'COHERE_URL',
                'default_url' => 'https://api.cohere.com/v1',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => false,
            ],
            'together' => [
                'name' => 'Together AI',
                'driver' => 'openai',
                'env_key' => 'TOGETHER_API_KEY',
                'env_url' => 'TOGETHER_URL',
                'default_url' => 'https://api.together.ai/v1',
                'category' => 'cloud',
                'requires_key' => true,
                'requires_url' => false,
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => false,
            ],
            'custom' => [
                'name' => 'Custom OpenAI-Compatible',
                'driver' => 'openai',
                'env_key' => 'AI_CHAT_CUSTOM_KEY',
                'env_url' => 'AI_CHAT_CUSTOM_URL',
                'default_url' => null,
                'category' => 'local',
                'requires_key' => false,
                'requires_url' => true,
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => false,
                'supports_audio' => false,
            ],
        ];
    }

    public static function providerAliases(): array
    {
        return [
            'zhipu' => 'glm',
            'zhipuai' => 'glm',
            'zai' => 'glm',
            'grok' => 'xai',
        ];
    }

    public static function resolveProviderAlias(string $key): string
    {
        $aliases = static::providerAliases();

        return $aliases[$key] ?? $key;
    }

    public static function models(string $provider): array
    {
        $provider = static::resolveProviderAlias($provider);

        return match ($provider) {
            'openai' => static::openaiModels(),
            'anthropic' => static::anthropicModels(),
            'gemini' => static::geminiModels(),
            'deepseek' => static::deepseekModels(),
            'groq' => static::groqModels(),
            'mistral' => static::mistralModels(),
            'xai' => static::xaiModels(),
            'openrouter' => static::openrouterModels(),
            'glm' => static::glmModels(),
            'azure_openai' => static::azureOpenaiModels(),
            'bedrock' => static::bedrockModels(),
            'ollama' => static::ollamaModels(),
            'cohere' => static::cohereModels(),
            'together' => static::togetherModels(),
            'custom' => [],
            default => [],
        };
    }

    protected static function anthropicModels(): array
    {
        return [
            'claude-opus-4-8' => ['name' => 'Claude Opus 4.8 (Smartest)', 'default' => false, 'tier' => 'smartest'],
            'claude-sonnet-4-6' => ['name' => 'Claude Sonnet 4.6 (Recommended)', 'default' => true, 'tier' => 'default'],
            'claude-haiku-4-5-20251001' => ['name' => 'Claude Haiku 4.5 (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'claude-opus-4-7' => ['name' => 'Claude Opus 4.7', 'default' => false, 'tier' => 'smartest'],
            'claude-opus-4-6' => ['name' => 'Claude Opus 4.6', 'default' => false, 'tier' => 'smartest'],
            'claude-sonnet-4-5-20250929' => ['name' => 'Claude Sonnet 4.5', 'default' => false, 'tier' => 'default'],
        ];
    }

    protected static function geminiModels(): array
    {
        return [
            'gemini-3.5-flash-preview' => ['name' => 'Gemini 3.5 Flash (Latest)', 'default' => false, 'tier' => 'default'],
            'gemini-3.1-pro-preview' => ['name' => 'Gemini 3.1 Pro Preview (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'gemini-3-flash-preview' => ['name' => 'Gemini 3 Flash Preview (Recommended)', 'default' => true, 'tier' => 'default'],
            'gemini-3.1-flash-lite-preview' => ['name' => 'Gemini 3.1 Flash Lite Preview (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'gemini-2.5-pro' => ['name' => 'Gemini 2.5 Pro (Stable)', 'default' => false, 'tier' => 'smartest'],
            'gemini-2.5-flash' => ['name' => 'Gemini 2.5 Flash (Stable)', 'default' => false, 'tier' => 'default'],
            'gemini-2.5-flash-lite' => ['name' => 'Gemini 2.5 Flash Lite (Stable)', 'default' => false, 'tier' => 'cheapest'],
            'gemini-2.0-flash' => ['name' => 'Gemini 2.0 Flash (Deprecating Jun 2026)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function deepseekModels(): array
    {
        return [
            'deepseek-v4-flash' => ['name' => 'DeepSeek V4 Flash (Recommended)', 'default' => true, 'tier' => 'default'],
            'deepseek-v4-pro' => ['name' => 'DeepSeek V4 Pro (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'deepseek-chat' => ['name' => 'DeepSeek Chat (Legacy — retires Jul 24, 2026)', 'default' => false, 'tier' => 'default'],
            'deepseek-reasoner' => ['name' => 'DeepSeek Reasoner (Legacy — retires Jul 24, 2026)', 'default' => false, 'tier' => 'smartest'],
        ];
    }

    protected static function groqModels(): array
    {
        return [
            'llama-3.3-70b-versatile' => ['name' => 'Llama 3.3 70B (Recommended)', 'default' => true, 'tier' => 'default'],
            'llama-3.1-8b-instant' => ['name' => 'Llama 3.1 8B (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'meta-llama/llama-4-scout-17b-16e-instruct' => ['name' => 'Llama 4 Scout 17B (Vision)', 'default' => false, 'tier' => 'default'],
            'meta-llama/llama-4-maverick-17b-128e-instruct' => ['name' => 'Llama 4 Maverick 17B (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'moonshotai/kimi-k2-instruct-0905' => ['name' => 'Kimi K2 (Premium)', 'default' => false, 'tier' => 'smartest'],
            'qwen/qwen3-32b' => ['name' => 'Qwen3 32B', 'default' => false, 'tier' => 'default'],
            'openai/gpt-oss-120b' => ['name' => 'GPT-OSS 120B', 'default' => false, 'tier' => 'smartest'],
            'openai/gpt-oss-20b' => ['name' => 'GPT-OSS 20B (Fast)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function mistralModels(): array
    {
        return [
            'mistral-large-latest' => ['name' => 'Mistral Large (Latest)', 'default' => true, 'tier' => 'default'],
            'mistral-medium-latest' => ['name' => 'Mistral Medium (Agentic)', 'default' => false, 'tier' => 'default'],
            'mistral-small-latest' => ['name' => 'Mistral Small (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'codestral-latest' => ['name' => 'Codestral (Code)', 'default' => false, 'tier' => 'default'],
            'devstral-2' => ['name' => 'Devstral 2 (Code Agent)', 'default' => false, 'tier' => 'smartest'],
            'magistral-medium' => ['name' => 'Magistral Medium (Reasoning)', 'default' => false, 'tier' => 'smartest'],
            'magistral-small' => ['name' => 'Magistral Small (Open Reasoning)', 'default' => false, 'tier' => 'cheapest'],
            'open-mistral-nemo' => ['name' => 'Mistral Nemo 12B (Open)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function xaiModels(): array
    {
        return [
            'grok-4.3' => ['name' => 'Grok 4.3 (Recommended)', 'default' => true, 'tier' => 'default'],
            'grok-4.20' => ['name' => 'Grok 4.20 (Legacy — alias for 4.3)', 'default' => false, 'tier' => 'smartest'],
        ];
    }

    protected static function openrouterModels(): array
    {
        return [
            'openrouter/free' => ['name' => 'Free Models Router (Auto)', 'default' => true, 'tier' => 'cheapest'],
            'anthropic/claude-sonnet-4.6' => ['name' => 'Claude Sonnet 4.6 (Recommended)', 'default' => false, 'tier' => 'default'],
            'anthropic/claude-haiku-4.5' => ['name' => 'Claude Haiku 4.5 (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'anthropic/claude-opus-4.6' => ['name' => 'Claude Opus 4.6 (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'openai/gpt-5.4' => ['name' => 'GPT-5.4', 'default' => false, 'tier' => 'default'],
            'openai/gpt-4o' => ['name' => 'GPT-4o', 'default' => false, 'tier' => 'default'],
            'google/gemini-3-flash-preview' => ['name' => 'Gemini 3 Flash Preview', 'default' => false, 'tier' => 'cheapest'],
            'deepseek/deepseek-v4-flash' => ['name' => 'DeepSeek V4 Flash', 'default' => false, 'tier' => 'cheapest'],
            'deepseek/deepseek-v4-pro' => ['name' => 'DeepSeek V4 Pro', 'default' => false, 'tier' => 'smartest'],
            'deepseek/deepseek-v4-flash:free' => ['name' => 'DeepSeek V4 Flash (Free)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function glmModels(): array
    {
        return [
            'glm-5.1' => ['name' => 'GLM-5.1 (Smartest)', 'default' => true, 'tier' => 'smartest'],
            'glm-5-turbo' => ['name' => 'GLM-5-Turbo (Fast)', 'default' => false, 'tier' => 'default'],
            'glm-5' => ['name' => 'GLM-5 (Flagship)', 'default' => false, 'tier' => 'smartest'],
            'glm-4.7' => ['name' => 'GLM-4.7 (Coding)', 'default' => false, 'tier' => 'default'],
            'glm-4.7-flash' => ['name' => 'GLM-4.7 Flash (Free)', 'default' => false, 'tier' => 'cheapest'],
            'glm-4.7-flashx' => ['name' => 'GLM-4.7 FlashX (Cheap)', 'default' => false, 'tier' => 'cheapest'],
            'glm-4.6' => ['name' => 'GLM-4.6', 'default' => false, 'tier' => 'default'],
            'glm-4.5' => ['name' => 'GLM-4.5', 'default' => false, 'tier' => 'default'],
            'glm-4.5-air' => ['name' => 'GLM-4.5 Air (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'glm-4.5-airx' => ['name' => 'GLM-4.5 AirX (Premium)', 'default' => false, 'tier' => 'smartest'],
            'glm-4.5-x' => ['name' => 'GLM-4.5 X (Premium)', 'default' => false, 'tier' => 'smartest'],
            'glm-4.5-flash' => ['name' => 'GLM-4.5 Flash (Free)', 'default' => false, 'tier' => 'cheapest'],
            'glm-4-32b-0414-128k' => ['name' => 'GLM-4-32B (Cheapest)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function azureOpenaiModels(): array
    {
        return [
            'gpt-5.4' => ['name' => 'GPT-5.4 (Deployment)', 'default' => true, 'tier' => 'default'],
            'gpt-4o' => ['name' => 'GPT-4o (Deployment)', 'default' => false, 'tier' => 'default'],
            'gpt-4o-mini' => ['name' => 'GPT-4o Mini (Deployment)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function bedrockModels(): array
    {
        return [
            'us.anthropic.claude-sonnet-4-6-v1:0' => ['name' => 'Claude Sonnet 4.6 (Recommended)', 'default' => true, 'tier' => 'default'],
            'us.anthropic.claude-sonnet-4-5-20250929-v1:0' => ['name' => 'Claude Sonnet 4.5', 'default' => false, 'tier' => 'default'],
            'us.anthropic.claude-haiku-4-5-20251001-v1:0' => ['name' => 'Claude Haiku 4.5 (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'us.anthropic.claude-opus-4-6-v1' => ['name' => 'Claude Opus 4.6 (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'amazon.titan-embed-text-v2:0' => ['name' => 'Titan Embeddings V2', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function ollamaModels(): array
    {
        return [
            'llama3.1:8b' => ['name' => 'Llama 3.1 8B (Recommended)', 'default' => true, 'tier' => 'cheapest'],
            'llama3.1:70b' => ['name' => 'Llama 3.1 70B', 'default' => false, 'tier' => 'smartest'],
            'llama3.3:70b' => ['name' => 'Llama 3.3 70B', 'default' => false, 'tier' => 'smartest'],
            'qwen2.5:7b' => ['name' => 'Qwen 2.5 7B', 'default' => false, 'tier' => 'cheapest'],
            'qwen2.5:32b' => ['name' => 'Qwen 2.5 32B', 'default' => false, 'tier' => 'default'],
            'qwen2.5:72b' => ['name' => 'Qwen 2.5 72B', 'default' => false, 'tier' => 'smartest'],
            'deepseek-r1:8b' => ['name' => 'DeepSeek R1 8B', 'default' => false, 'tier' => 'cheapest'],
            'deepseek-r1:70b' => ['name' => 'DeepSeek R1 70B', 'default' => false, 'tier' => 'smartest'],
            'codellama:7b' => ['name' => 'Code Llama 7B', 'default' => false, 'tier' => 'cheapest'],
            'codellama:13b' => ['name' => 'Code Llama 13B', 'default' => false, 'tier' => 'default'],
            'mistral:7b' => ['name' => 'Mistral 7B', 'default' => false, 'tier' => 'cheapest'],
            'gemma2:9b' => ['name' => 'Gemma 2 9B', 'default' => false, 'tier' => 'cheapest'],
            'phi3:14b' => ['name' => 'Phi-3 14B', 'default' => false, 'tier' => 'default'],
            'nomic-embed-text' => ['name' => 'Nomic Embed Text (Embeddings)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function cohereModels(): array
    {
        return [
            'command-a-plus-05-2026' => ['name' => 'Command A+ (Latest Flagship)', 'default' => false, 'tier' => 'smartest'],
            'command-a-03-2025' => ['name' => 'Command A (Recommended)', 'default' => true, 'tier' => 'default'],
            'command-a-reasoning-08-2025' => ['name' => 'Command A Reasoning', 'default' => false, 'tier' => 'smartest'],
            'command-a-vision-07-2025' => ['name' => 'Command A Vision', 'default' => false, 'tier' => 'default'],
            'command-r7b-12-2024' => ['name' => 'Command R7B (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'command-r-08-2024' => ['name' => 'Command R', 'default' => false, 'tier' => 'default'],
            'command-r-plus-08-2024' => ['name' => 'Command R+', 'default' => false, 'tier' => 'smartest'],
        ];
    }

    protected static function togetherModels(): array
    {
        return [
            'deepseek-ai/DeepSeek-V4-Pro' => ['name' => 'DeepSeek V4 Pro (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'deepseek-ai/DeepSeek-V4-Flash' => ['name' => 'DeepSeek V4 Flash (Recommended)', 'default' => true, 'tier' => 'default'],
            'deepseek-ai/DeepSeek-R1-0528' => ['name' => 'DeepSeek R1 (Reasoning)', 'default' => false, 'tier' => 'smartest'],
            'Qwen/Qwen3.7-Max' => ['name' => 'Qwen 3.7 Max', 'default' => false, 'tier' => 'smartest'],
            'Qwen/Qwen3-235B-A22B-Instruct-2507-tput' => ['name' => 'Qwen3 235B (Fast)', 'default' => false, 'tier' => 'default'],
            'meta-llama/Llama-3.3-70B-Instruct-Turbo' => ['name' => 'Llama 3.3 70B Turbo', 'default' => false, 'tier' => 'default'],
            'meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8' => ['name' => 'Llama 4 Maverick 17B', 'default' => false, 'tier' => 'default'],
            'google/gemma-4-31B-it' => ['name' => 'Gemma 4 31B', 'default' => false, 'tier' => 'default'],
            'mistralai/Ministral-3-14B-Instruct-2512' => ['name' => 'Ministral 3 14B (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'MiniMaxAI/MiniMax-M2.7' => ['name' => 'MiniMax M2.7', 'default' => false, 'tier' => 'default'],
        ];
    }

    public static function providerGroups(): array
    {
        return [
            'Cloud Providers' => ['openai', 'anthropic', 'gemini', 'deepseek', 'groq', 'mistral', 'xai', 'openrouter', 'glm', 'cohere', 'together'],
            'Enterprise' => ['azure_openai', 'bedrock'],
            'Local / Self-Hosted' => ['ollama', 'custom'],
        ];
    }
}
