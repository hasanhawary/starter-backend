<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Pipeline\ChatPayload;
use Closure;

class SendToProvider
{
    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if ($payload->agent && method_exists($payload->agent, 'isFaked') && $payload->agent->isFaked()) {
            $payload->response = (string) $payload->agent->prompt($payload->message);

            return $next($payload);
        }

        if (! $payload->agent) {
            $payload->addError('AI provider is not configured. Please set up an AI provider first.', 503);

            return $payload;
        }

        if ($payload->streaming) {
            return $this->handleStreaming($payload, $next);
        }

        return $this->handleSync($payload, $next);
    }

    protected function handleSync(ChatPayload $payload, Closure $next): ChatPayload
    {
        try {
            $response = $payload->agent->prompt($payload->message);
        } catch (\Throwable $e) {
            $payload->addError('AI provider error: '.$e->getMessage(), 503);

            return $payload;
        }

        $payload->rawResponse = $response;
        $payload->response = $response->text ?? '';

        if (isset($response->usage)) {
            $payload->setMetadata('usage', $response->usage);
        }

        return $next($payload);
    }

    protected function handleStreaming(ChatPayload $payload, Closure $next): ChatPayload
    {
        try {
            $stream = $payload->agent->stream($payload->message);
            $fullContent = '';

            foreach ($stream as $event) {
                $content = (string) ($event->text ?? '');
                $fullContent .= $content;
            }

            $payload->response = $fullContent;
            $payload->rawResponse = $stream;

            return $next($payload);
        } catch (\Throwable $e) {
            $payload->addError('AI provider error: '.$e->getMessage(), 503);

            return $payload;
        }
    }
}
