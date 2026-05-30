<?php

namespace AiChat\Gateway;

use Generator;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Contracts\Gateway\TextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Gateway\Concerns\HandlesFailoverErrors;
use Laravel\Ai\Gateway\Concerns\InvokesTools;
use Laravel\Ai\Gateway\Concerns\ParsesServerSentEvents;
use Laravel\Ai\Gateway\DeepSeek\Concerns\BuildsTextRequests;
use Laravel\Ai\Gateway\DeepSeek\Concerns\HandlesTextStreaming;
use Laravel\Ai\Gateway\DeepSeek\Concerns\MapsAttachments;
use Laravel\Ai\Gateway\DeepSeek\Concerns\MapsMessages;
use Laravel\Ai\Gateway\DeepSeek\Concerns\MapsTools;
use Laravel\Ai\Gateway\DeepSeek\Concerns\ParsesTextResponses;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Responses\TextResponse;

class GlmGateway implements TextGateway
{
    use BuildsTextRequests;
    use Concerns\CreatesGlmClient;
    use HandlesFailoverErrors;
    use HandlesTextStreaming;
    use InvokesTools;
    use MapsAttachments;
    use MapsMessages;
    use MapsTools;
    use ParsesServerSentEvents;
    use ParsesTextResponses;

    public function __construct(protected Dispatcher $events)
    {
        $this->initializeToolCallbacks();
    }

    public function generateText(
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages = [],
        array $tools = [],
        ?array $schema = null,
        ?TextGenerationOptions $options = null,
        ?int $timeout = null,
    ): TextResponse {
        $body = $this->buildTextRequestBody(
            $provider,
            $model,
            $instructions,
            $messages,
            $tools,
            $schema,
            $options,
        );

        $response = $this->withErrorHandling(
            $provider->name(),
            fn () => $this->client($provider, $timeout)->post('chat/completions', $body),
        );

        $data = $response->json();

        $this->validateTextResponse($data);

        return $this->parseTextResponse(
            $data,
            $provider,
            filled($schema),
            $tools,
            $schema,
            $options,
            $instructions,
            $messages,
            $timeout,
        );
    }

    public function streamText(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages = [],
        array $tools = [],
        ?array $schema = null,
        ?TextGenerationOptions $options = null,
        ?int $timeout = null,
    ): Generator {
        $body = $this->buildTextRequestBody(
            $provider,
            $model,
            $instructions,
            $messages,
            $tools,
            $schema,
            $options,
        );

        $body['stream'] = true;
        $body['stream_options'] = ['include_usage' => true];

        $response = $this->withErrorHandling(
            $provider->name(),
            fn () => $this->client($provider, $timeout)
                ->withOptions(['stream' => true])
                ->post('chat/completions', $body),
        );

        yield from $this->processTextStream(
            $invocationId,
            $provider,
            $model,
            $tools,
            $schema,
            $options,
            $response->getBody(),
            $instructions,
            $messages,
            0,
            null,
            [],
            $timeout,
        );
    }
}
