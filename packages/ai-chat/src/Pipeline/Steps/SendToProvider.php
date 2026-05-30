<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Contracts\LLMProviderInterface;
use AiChat\Pipeline\ChatPayload;
use Closure;
use Illuminate\Support\Facades\App;

class SendToProvider
{
    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $provider = $this->resolveProvider();

        $tools = $this->buildToolSchemas($payload);

        $options = $this->buildOptions($payload);

        if ($payload->streaming) {
            return $this->handleStreaming($payload, $provider, $tools, $options, $next);
        }

        $response = $provider->send($payload->messages, $tools, $options);

        $payload->rawResponse = $response;
        $payload->response = $response['content'] ?? $response['text'] ?? '';

        if (isset($response['usage'])) {
            $payload->setMetadata('usage', $response['usage']);
        }

        return $next($payload);
    }

    protected function handleStreaming(ChatPayload $payload, LLMProviderInterface $provider, array $tools, array $options, Closure $next): ChatPayload
    {
        $fullContent = '';

        foreach ($provider->stream($payload->messages, $tools, $options) as $chunk) {
            $content = is_array($chunk) ? ($chunk['content'] ?? $chunk['text'] ?? '') : (string) $chunk;
            $fullContent .= $content;
        }

        $payload->response = $fullContent;
        $payload->rawResponse = ['content' => $fullContent];

        return $next($payload);
    }

    protected function resolveProvider(): LLMProviderInterface
    {
        $providerName = config('ai-chat.provider', 'glm');

        return App::make(LLMProviderInterface::class, ['provider' => $providerName]);
    }

    protected function buildToolSchemas(ChatPayload $payload): array
    {
        return array_values(array_map(fn ($tool) => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'inputSchema' => $tool->schema(),
        ], $payload->tools));
    }

    protected function buildOptions(ChatPayload $payload): array
    {
        return array_filter([
            'model' => config('ai-chat.model', 'glm-5.1'),
            'max_tokens' => config('ai-chat.max_tokens', 65536),
            'temperature' => config('ai-chat.temperature', 1.0),
            'timeout' => config('ai-chat.timeout', 300),
        ]);
    }
}
