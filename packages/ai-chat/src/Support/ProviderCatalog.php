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
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => true,
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'driver' => 'anthropic',
                'env_key' => 'ANTHROPIC_API_KEY',
                'env_url' => 'ANTHROPIC_URL',
                'default_url' => 'https://api.anthropic.com/v1',
                'category' => 'cloud',
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'gemini' => [
                'name' => 'Google Gemini',
                'driver' => 'gemini',
                'env_key' => 'GEMINI_API_KEY',
                'env_url' => 'GEMINI_URL',
                'default_url' => 'https://generativelanguage.googleapis.com/v1beta/',
                'category' => 'cloud',
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
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'groq' => [
                'name' => 'Groq',
                'driver' => 'groq',
                'env_key' => 'GROQ_API_KEY',
                'env_url' => 'GROQ_URL',
                'default_url' => 'https://api.groq.com/openai/v1',
                'category' => 'cloud',
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'mistral' => [
                'name' => 'Mistral',
                'driver' => 'mistral',
                'env_key' => 'MISTRAL_API_KEY',
                'env_url' => 'MISTRAL_URL',
                'default_url' => 'https://api.mistral.ai/v1',
                'category' => 'cloud',
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'xai' => [
                'name' => 'xAI (Grok)',
                'driver' => 'xai',
                'env_key' => 'XAI_API_KEY',
                'env_url' => 'XAI_URL',
                'default_url' => 'https://api.x.ai/v1',
                'category' => 'cloud',
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => true,
                'supports_audio' => false,
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'driver' => 'openrouter',
                'env_key' => 'OPENROUTER_API_KEY',
                'env_url' => null,
                'default_url' => null,
                'category' => 'cloud',
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => true,
                'supports_audio' => true,
            ],
            'glm' => [
                'name' => 'GLM (ZhipuAI)',
                'driver' => 'glm',
                'env_key' => 'GLM_API_KEY',
                'env_url' => 'GLM_API_URL',
                'default_url' => 'https://open.bigmodel.cn/api/paas/v4',
                'category' => 'cloud',
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
                'supports_text' => true,
                'supports_embeddings' => true,
                'supports_images' => false,
                'supports_audio' => false,
            ],
            'custom' => [
                'name' => 'Custom / OpenAI-Compatible',
                'driver' => 'openai',
                'env_key' => 'AI_CHAT_CUSTOM_KEY',
                'env_url' => 'AI_CHAT_CUSTOM_URL',
                'default_url' => null,
                'category' => 'local',
                'supports_text' => true,
                'supports_embeddings' => false,
                'supports_images' => false,
                'supports_audio' => false,
            ],
        ];
    }

    public static function models(string $provider): array
    {
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
            'custom' => [],
            default => [],
        };
    }

    public static function defaultModel(string $provider): string
    {
        $models = static::models($provider);

        foreach ($models as $id => $info) {
            if (($info['default'] ?? false) === true) {
                return $id;
            }
        }

        return array_key_first($models) ?? '';
    }

    protected static function openaiModels(): array
    {
        return [
            'gpt-5.5' => ['name' => 'GPT-5.5 (Latest Flagship)', 'default' => false, 'tier' => 'smartest'],
            'gpt-5.4' => ['name' => 'GPT-5.4 (Recommended)', 'default' => true, 'tier' => 'default'],
            'gpt-5.4-mini' => ['name' => 'GPT-5.4 Mini (Fast & Cheap)', 'default' => false, 'tier' => 'cheapest'],
            'gpt-5.4-nano' => ['name' => 'GPT-5.4 Nano (Fastest)', 'default' => false, 'tier' => 'cheapest'],
            'gpt-5.4-pro' => ['name' => 'GPT-5.4 Pro (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'o3' => ['name' => 'o3 (Reasoning)', 'default' => false, 'tier' => 'smartest'],
            'o3-mini' => ['name' => 'o3 Mini (Reasoning)', 'default' => false, 'tier' => 'cheapest'],
            'o4-mini' => ['name' => 'o4 Mini (Reasoning)', 'default' => false, 'tier' => 'default'],
            'gpt-4o' => ['name' => 'GPT-4o', 'default' => false, 'tier' => 'default'],
            'gpt-4o-mini' => ['name' => 'GPT-4o Mini', 'default' => false, 'tier' => 'cheapest'],
            'gpt-4-turbo' => ['name' => 'GPT-4 Turbo (Legacy)', 'default' => false, 'tier' => 'default'],
            'gpt-3.5-turbo' => ['name' => 'GPT-3.5 Turbo (Legacy)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function anthropicModels(): array
    {
        return [
            'claude-sonnet-4-6' => ['name' => 'Claude Sonnet 4.6 (Recommended)', 'default' => true, 'tier' => 'default'],
            'claude-haiku-4-5-20251001' => ['name' => 'Claude Haiku 4.5 (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'claude-opus-4-7' => ['name' => 'Claude Opus 4.7 (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'claude-opus-4-6' => ['name' => 'Claude Opus 4.6', 'default' => false, 'tier' => 'smartest'],
            'claude-sonnet-4-5-20250929' => ['name' => 'Claude Sonnet 4.5', 'default' => false, 'tier' => 'default'],
            'claude-opus-4-5-20251101' => ['name' => 'Claude Opus 4.5', 'default' => false, 'tier' => 'smartest'],
            'claude-opus-4-1-20250805' => ['name' => 'Claude Opus 4.1', 'default' => false, 'tier' => 'smartest'],
            'claude-3-5-sonnet-20241022' => ['name' => 'Claude 3.5 Sonnet (Legacy)', 'default' => false, 'tier' => 'default'],
            'claude-3-haiku-20240307' => ['name' => 'Claude 3 Haiku (Legacy)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function geminiModels(): array
    {
        return [
            'gemini-3-flash-preview' => ['name' => 'Gemini 3 Flash (Recommended)', 'default' => true, 'tier' => 'default'],
            'gemini-3.1-pro-preview' => ['name' => 'Gemini 3.1 Pro (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'gemini-3.1-flash-lite-preview' => ['name' => 'Gemini 3.1 Flash Lite (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'gemini-2.5-pro' => ['name' => 'Gemini 2.5 Pro', 'default' => false, 'tier' => 'smartest'],
            'gemini-2.5-flash' => ['name' => 'Gemini 2.5 Flash', 'default' => false, 'tier' => 'default'],
            'gemini-2.0-flash' => ['name' => 'Gemini 2.0 Flash', 'default' => false, 'tier' => 'cheapest'],
            'gemini-2.0-flash-lite' => ['name' => 'Gemini 2.0 Flash Lite', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function deepseekModels(): array
    {
        return [
            'deepseek-chat' => ['name' => 'DeepSeek Chat (Recommended)', 'default' => true, 'tier' => 'default'],
            'deepseek-reasoner' => ['name' => 'DeepSeek Reasoner (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'deepseek-v4-flash' => ['name' => 'DeepSeek V4 Flash', 'default' => false, 'tier' => 'cheapest'],
            'deepseek-v4-pro' => ['name' => 'DeepSeek V4 Pro', 'default' => false, 'tier' => 'smartest'],
        ];
    }

    protected static function groqModels(): array
    {
        return [
            'openai/gpt-oss-120b' => ['name' => 'GPT-OSS 120B (Recommended)', 'default' => true, 'tier' => 'default'],
            'openai/gpt-oss-20b' => ['name' => 'GPT-OSS 20B (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'llama-3.3-70b-versatile' => ['name' => 'Llama 3.3 70B', 'default' => false, 'tier' => 'default'],
            'llama-3.1-8b-instant' => ['name' => 'Llama 3.1 8B (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'meta-llama/llama-4-scout-17b-16e-instruct' => ['name' => 'Llama 4 Scout', 'default' => false, 'tier' => 'default'],
            'qwen/qwen3-32b' => ['name' => 'Qwen3 32B', 'default' => false, 'tier' => 'default'],
        ];
    }

    protected static function mistralModels(): array
    {
        return [
            'mistral-medium-latest' => ['name' => 'Mistral Medium (Recommended)', 'default' => true, 'tier' => 'default'],
            'mistral-small-latest' => ['name' => 'Mistral Small (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'mistral-large-latest' => ['name' => 'Mistral Large (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'mistral-medium-2508' => ['name' => 'Mistral Medium 3.1', 'default' => false, 'tier' => 'default'],
            'mistral-small-2506' => ['name' => 'Mistral Small 3.2', 'default' => false, 'tier' => 'cheapest'],
            'open-mistral-nemo' => ['name' => 'Mistral Nemo 12B (Open)', 'default' => false, 'tier' => 'cheapest'],
            'devstral-2512' => ['name' => 'Devstral 2 (Code)', 'default' => false, 'tier' => 'default'],
            'codestral-2508' => ['name' => 'Codestral (Code)', 'default' => false, 'tier' => 'default'],
        ];
    }

    protected static function xaiModels(): array
    {
        return [
            'grok-4-1-fast-reasoning' => ['name' => 'Grok 4.1 Fast Reasoning (Recommended)', 'default' => true, 'tier' => 'default'],
            'grok-4.3' => ['name' => 'Grok 4.3 (Latest)', 'default' => false, 'tier' => 'smartest'],
            'grok-4.20-0309-reasoning' => ['name' => 'Grok 4.20 Reasoning', 'default' => false, 'tier' => 'smartest'],
            'grok-4.20-0309-non-reasoning' => ['name' => 'Grok 4.20', 'default' => false, 'tier' => 'default'],
            'grok-4.20-multi-agent-0309' => ['name' => 'Grok 4.20 Multi-Agent', 'default' => false, 'tier' => 'smartest'],
        ];
    }

    protected static function openrouterModels(): array
    {
        return [
            'anthropic/claude-sonnet-4.6' => ['name' => 'Claude Sonnet 4.6 (Recommended)', 'default' => true, 'tier' => 'default'],
            'anthropic/claude-haiku-4.5' => ['name' => 'Claude Haiku 4.5 (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'anthropic/claude-opus-4.6' => ['name' => 'Claude Opus 4.6 (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'openai/gpt-5.4' => ['name' => 'GPT-5.4', 'default' => false, 'tier' => 'default'],
            'openai/gpt-4o' => ['name' => 'GPT-4o', 'default' => false, 'tier' => 'default'],
            'google/gemini-3-flash-preview' => ['name' => 'Gemini 3 Flash', 'default' => false, 'tier' => 'cheapest'],
            'deepseek/deepseek-chat' => ['name' => 'DeepSeek Chat', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function glmModels(): array
    {
        return [
            'glm-5.1' => ['name' => 'GLM-5.1 (Recommended)', 'default' => true, 'tier' => 'default'],
            'glm-4-plus' => ['name' => 'GLM-4 Plus (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'glm-4-flash' => ['name' => 'GLM-4 Flash (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'glm-4-long' => ['name' => 'GLM-4 Long (Long Context)', 'default' => false, 'tier' => 'default'],
            'glm-4-air' => ['name' => 'GLM-4 Air', 'default' => false, 'tier' => 'cheapest'],
            'glm-4-airx' => ['name' => 'GLM-4 AirX', 'default' => false, 'tier' => 'default'],
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
            'us.anthropic.claude-sonnet-4-5-20250929-v1:0' => ['name' => 'Claude Sonnet 4.5 (Recommended)', 'default' => true, 'tier' => 'default'],
            'us.anthropic.claude-haiku-4-5-20251001-v1:0' => ['name' => 'Claude Haiku 4.5 (Fast)', 'default' => false, 'tier' => 'cheapest'],
            'us.anthropic.claude-opus-4-6-v1' => ['name' => 'Claude Opus 4.6 (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'amazon.titan-embed-text-v2:0' => ['name' => 'Titan Embeddings V2', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    protected static function ollamaModels(): array
    {
        return [
            'llama3.1:8b' => ['name' => 'Llama 3.1 8B (Recommended)', 'default' => true, 'tier' => 'cheapest'],
            'llama3.1:70b' => ['name' => 'Llama 3.1 70B (Most Capable)', 'default' => false, 'tier' => 'smartest'],
            'llama3.3:70b' => ['name' => 'Llama 3.3 70B', 'default' => false, 'tier' => 'smartest'],
            'qwen2.5:7b' => ['name' => 'Qwen 2.5 7B', 'default' => false, 'tier' => 'cheapest'],
            'qwen2.5:32b' => ['name' => 'Qwen 2.5 32B', 'default' => false, 'tier' => 'default'],
            'qwen2.5:72b' => ['name' => 'Qwen 2.5 72B', 'default' => false, 'tier' => 'smartest'],
            'codellama:7b' => ['name' => 'Code Llama 7B', 'default' => false, 'tier' => 'cheapest'],
            'codellama:13b' => ['name' => 'Code Llama 13B', 'default' => false, 'tier' => 'default'],
            'mistral:7b' => ['name' => 'Mistral 7B', 'default' => false, 'tier' => 'cheapest'],
            'deepseek-coder-v2:16b' => ['name' => 'DeepSeek Coder V2 16B', 'default' => false, 'tier' => 'default'],
            'gemma2:9b' => ['name' => 'Gemma 2 9B', 'default' => false, 'tier' => 'cheapest'],
            'phi3:14b' => ['name' => 'Phi-3 14B', 'default' => false, 'tier' => 'default'],
            'nomic-embed-text' => ['name' => 'Nomic Embed Text (Embeddings)', 'default' => false, 'tier' => 'cheapest'],
        ];
    }

    public static function providerGroups(): array
    {
        return [
            'Cloud Providers' => ['openai', 'anthropic', 'gemini', 'deepseek', 'groq', 'mistral', 'xai', 'openrouter', 'glm'],
            'Enterprise' => ['azure_openai', 'bedrock'],
            'Local / Self-Hosted' => ['ollama', 'custom'],
        ];
    }
}
