<?php

namespace AiChat\Http\Controllers;

use AiChat\Agents\ChatAgent;
use AiChat\Chat\ConversationManager;
use AiChat\Chat\HistorySelector;
use AiChat\Chat\MessageManager;
use AiChat\Chat\StreamManager;
use AiChat\Http\Requests\FeedbackRequest;
use AiChat\Http\Requests\GetConversationRequest;
use AiChat\Http\Requests\ListConversationsRequest;
use AiChat\Http\Requests\SendMessageRequest;
use AiChat\Http\Resources\ConversationResource;
use AiChat\Http\Resources\FeedbackResource;
use AiChat\Http\Resources\MessageResource;
use AiChat\Models\AiChatConversation;
use AiChat\Models\AiFeedback;
use AiChat\Models\AiUsageLog;
use AiChat\Pipeline\ChatPayload;
use AiChat\Pipeline\ChatPipeline;
use AiChat\Pipeline\ExecutionPlan;
use AiChat\Pipeline\Steps\ApplyPolicies;
use AiChat\Pipeline\Steps\PlanStep;
use AiChat\Pipeline\Steps\ResolveAgent;
use AiChat\Pipeline\Steps\ResolveContext;
use AiChat\Pipeline\Steps\ResolveTools;
use AiChat\Pipeline\Steps\ResolveUser;
use AiChat\Pipeline\Steps\RetrieveKnowledge;
use AiChat\Pipeline\Steps\RetrieveMemory;
use AiChat\Pipeline\Steps\ValidateMessage;
use AiChat\Prompt\SystemPromptBuilder;
use AiChat\Response\FinalResponseFormatter;
use AiChat\Storage\AnonymousConversationStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
        private readonly MessageManager $messageManager,
        private readonly ChatPipeline $pipeline,
        private readonly StreamManager $streamManager,
        private readonly FinalResponseFormatter $formatter,
        private readonly SystemPromptBuilder $promptBuilder,
    ) {}

    public function sendMessage(SendMessageRequest $request): JsonResponse|StreamedResponse
    {
        try {
            if ($request->boolean('stream', false)) {
                return $this->streamMessage($request);
            }

            return $this->processSync($request);
        } catch (\Throwable $e) {
            return failResponse($e->getMessage(), [], 500);
        }
    }

    protected function processSync(SendMessageRequest $request): JsonResponse
    {
        $payload = $this->buildPayload($request, streaming: false);

        $payload = $this->pipeline->process($payload);

        if ($payload->hasErrors()) {
            return failResponse($payload->firstError(), [], $payload->firstErrorCode());
        }

        $conversationId = $payload->conversationId();

        if (! $conversationId) {
            return failResponse('Failed to create conversation.', [], 500);
        }

        $response = $this->formatter->format(
            $payload->response ?? '',
            $payload->message,
            $payload->executionPlan,
        );

        $payload->response = $response;

        return successResponse([
            'conversation_id' => $conversationId,
            'message' => [
                'id' => null,
                'role' => 'assistant',
                'content' => $response,
                'usage' => $payload->metadata['usage'] ?? null,
                'created_at' => now()->toIso8601String(),
                'tool_calls' => (bool) ($payload->metadata['tool_calls_used'] ?? false),
            ],
            'plan' => $payload->executionPlan?->toArray() ?? null,
        ]);
    }

    public function streamMessage(SendMessageRequest $request): StreamedResponse
    {
        $payload = $this->buildPayload($request, streaming: true);
        $conversationId = $this->ensureConversation($request);

        if ($conversationId) {
            $payload->conversation = $this->conversationManager->get($conversationId);
        }

        $planningPayload = (clone $payload);
        $planningSteps = $this->getPlanningSteps();
        $planningPayload = app(Pipeline::class)
            ->send($planningPayload)
            ->through($planningSteps)
            ->thenReturn();

        $selectedToolNames = array_keys($planningPayload->tools ?? []);
        $systemPrompt = $this->promptBuilder->build($planningPayload);

        $agent = $this->buildChatAgent($request, $conversationId, $selectedToolNames, $systemPrompt, $planningPayload->executionPlan);

        $finalConversationId = $conversationId;

        return new StreamedResponse(function () use ($agent, $request, $finalConversationId, $payload, $planningPayload) {
            if ($finalConversationId) {
                $this->emitSse('data', ['conversation_id' => $finalConversationId], 'conversation_id');
            }

            $message = $request->validated('message');

            $this->streamManager->startStream($finalConversationId ?? 'unknown');

            try {
                $stream = $agent->stream($message);

                $fullContent = '';

                foreach ($stream as $event) {
                    $content = $this->extractStreamChunk($event);
                    $fullContent .= $content;

                    $this->captureStreamUsage($payload, $event);
                }

                $payload->response = $this->formatter->format($fullContent, $message, $planningPayload->executionPlan);

                if ($payload->response !== '') {
                    $this->emitSse('data', ['type' => 'text_delta', 'delta' => $payload->response]);

                    $this->streamManager->appendToStream($finalConversationId ?? 'unknown', $payload->response);
                }

                $this->persistStreamResponse($payload, $finalConversationId);

                $this->streamManager->endStream($finalConversationId ?? 'unknown', $payload->response);
            } catch (\Throwable $e) {
                $this->streamManager->abortStream($finalConversationId ?? 'unknown');

                $this->emitSse('data', ['error' => $e->getMessage()]);
            }

            echo "data: [DONE]\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function listConversations(ListConversationsRequest $request): JsonResponse
    {
        $sessionId = $request->attributes->get('ai_chat_session_id')
            ?? $request->validated('session_id');

        $conversations = $this->conversationManager->listForUser(
            $sessionId,
            $request->validated('per_page', 15),
        );

        return successResponse(ConversationResource::collection($conversations));
    }

    public function getConversation(string $id, GetConversationRequest $request): JsonResponse
    {
        $sessionId = $request->attributes->get('ai_chat_session_id')
            ?? $request->validated('session_id');

        $conversation = AiChatConversation::where('id', $id)
            ->where('session_id', $sessionId)
            ->with('messages')
            ->first();

        if (! $conversation) {
            return failResponse('Conversation not found.', [], 404);
        }

        return successResponse([
            'conversation' => new ConversationResource($conversation),
            'messages' => MessageResource::collection(
                $conversation->messages()->orderBy('created_at', 'asc')->get(),
            ),
        ]);
    }

    public function deleteConversation(string $id, GetConversationRequest $request): JsonResponse
    {
        $sessionId = $request->attributes->get('ai_chat_session_id')
            ?? $request->validated('session_id');

        $conversation = AiChatConversation::where('id', $id)
            ->where('session_id', $sessionId)
            ->first();

        if (! $conversation) {
            return failResponse('Conversation not found.', [], 404);
        }

        $conversation->messages()->delete();
        $conversation->delete();

        return successResponse(msg: 'Conversation deleted.');
    }

    public function submitFeedback(FeedbackRequest $request): JsonResponse
    {
        $feedback = AiFeedback::create([
            'message_id' => $request->validated('message_id'),
            'rating' => $request->validated('rating'),
            'comment' => $request->validated('comment'),
        ]);

        return successResponse(new FeedbackResource($feedback), 'Feedback submitted.');
    }

    protected function buildPayload(SendMessageRequest $request, bool $streaming = false): ChatPayload
    {
        $user = $request->attributes->get('ai_chat_user');
        $message = $request->validated('message');
        $conversationId = $request->validated('conversation_id');
        $sessionId = $request->validated('session_id');

        $payload = new ChatPayload($message, $user);
        $payload->streaming = $streaming;

        if ($conversationId) {
            $conversation = $this->conversationManager->get($conversationId);

            if ($conversation) {
                $payload->conversation = $conversation;
            }
        }

        $payload->setMetadata('session_id', $sessionId);
        $payload->setMetadata('locale', app()->getLocale());
        $payload->setMetadata('ip', request()->ip());

        return $payload;
    }

    protected function getPlanningSteps(): array
    {
        return [
            ValidateMessage::class,
            ResolveUser::class,
            ResolveAgent::class,
            PlanStep::class,
            ApplyPolicies::class,
            ResolveContext::class,
            RetrieveKnowledge::class,
            RetrieveMemory::class,
            ResolveTools::class,
        ];
    }

    protected function buildChatAgent(SendMessageRequest $request, ?string $conversationId, array $toolNames = [], ?string $systemPrompt = null, ?ExecutionPlan $plan = null): ChatAgent
    {
        $agent = new ChatAgent($systemPrompt ?? $request->validated('system_prompt'));
        $sessionId = $request->validated('session_id');

        if ($conversationId) {
            $agent->continue($conversationId, $sessionId);
        } else {
            $agent->forSession($sessionId);
        }

        if (! empty($toolNames)) {
            $agent->withTools($toolNames);
        }

        $agent->withToolContextPayload([
            'conversation_id' => $conversationId,
            'session_id' => $sessionId,
            'message' => $request->validated('message'),
        ]);

        if ($plan) {
            $message = $request->validated('message');
            $agent->withExecutionPlan($plan);
            $agent->withCurrentMessage($message);

            $selector = app(HistorySelector::class);
            $policy = $selector->select($plan, $message);
            $agent->withHistoryPolicy($policy);
        }

        return $agent;
    }

    protected function ensureConversation(SendMessageRequest $request): ?string
    {
        $conversationId = $request->validated('conversation_id');

        if ($conversationId) {
            return $conversationId;
        }

        $sessionId = $request->validated('session_id');
        $store = app(AnonymousConversationStore::class);

        return $store->storeConversation($sessionId, 'New Chat');
    }

    protected function persistStreamResponse(ChatPayload $payload, ?string $conversationId): void
    {
        if (! $conversationId || ! $payload->response) {
            return;
        }

        try {
            $userId = $payload->user?->getAuthIdentifier();
            $sessionId = $payload->metadata['session_id'] ?? null;

            $this->messageManager->storeUserMessage(
                $conversationId,
                $payload->message,
                is_string($sessionId) ? $sessionId : (is_string($userId) ? $userId : (string) ($userId ?? 'anonymous')),
                $payload->metadata,
            );

            $metadata = [];

            if (isset($payload->metadata['usage'])) {
                $metadata['usage'] = $payload->metadata['usage'];
            }

            $this->messageManager->storeAssistantMessage(
                $conversationId,
                $payload->response,
                $metadata,
            );

            $this->persistUsage($payload, $conversationId);
        } catch (\Throwable) {
        }
    }

    protected function extractStreamChunk(object $event): string
    {
        if (property_exists($event, 'delta') && is_string($event->delta)) {
            return $event->delta;
        }

        if (property_exists($event, 'text') && is_string($event->text)) {
            return $event->text;
        }

        if ($this->streamEventType($event) === 'text_delta') {
            // Fallback to empty string if text_delta event has no delta/text property.
            // This case is unlikely given the above checks.
            return '';
        }

        return '';
    }

    protected function shouldEmitStreamEvent(object $event): bool
    {
        $type = $this->streamEventType($event);

        if ($type === null) {
            return true;
        }

        return in_array($type, ['stream_start', 'text_start', 'text_delta', 'text_end', 'stream_end'], true);
    }

    protected function extractFinalVisibleStreamChunk(string $chunk, string &$buffer, bool &$insideFinal): string
    {
        if ($chunk === '') {
            return '';
        }

        $buffer .= $chunk;
        $visible = '';

        while ($buffer !== '') {
            if (! $insideFinal) {
                $start = stripos($buffer, '<final>');

                if ($start === false) {
                    $buffer = substr($buffer, max(0, strlen($buffer) - 7));

                    return $visible;
                }

                $buffer = substr($buffer, $start + 7);
                $insideFinal = true;
            }

            $end = stripos($buffer, '</final>');

            if ($end === false) {
                $bufferLength = mb_strlen($buffer);
                $safeLength = max(0, $bufferLength - 8);

                if ($safeLength === 0) {
                    return $visible;
                }

                $visible .= mb_substr($buffer, 0, $safeLength);
                $buffer = mb_substr($buffer, $safeLength);

                return $visible;
            }

            $visible .= substr($buffer, 0, $end);
            $buffer = substr($buffer, $end + 8);
            $insideFinal = false;
        }

        return $visible;
    }

    protected function formatStreamEvent(object $event, string $visibleChunk, string $fullContent, string $message, ?ExecutionPlan $plan, bool $streamedFinal): string
    {
        $type = $this->streamEventType($event);

        if ($type === 'text_delta') {
            if ($visibleChunk === '') {
                return '';
            }

            return (string) json_encode(['type' => 'text_delta', 'delta' => $visibleChunk]);
        }

        if ($type !== 'text_end') {
            return (string) $event;
        }

        if ($visibleChunk !== '') {
            return (string) json_encode(['type' => 'text_delta', 'delta' => $visibleChunk]);
        }

        if ($streamedFinal) {
            return (string) $event;
        }

        $content = $this->formatter->format($fullContent, $message, $plan);

        if ($content === '') {
            return '';
        }

        return (string) json_encode(['type' => 'text_delta', 'delta' => $content]);
    }

    protected function captureStreamUsage(ChatPayload $payload, object $event): void
    {
        if ($this->streamEventType($event) !== 'stream_end') {
            return;
        }

        if (property_exists($event, 'usage') && $event->usage !== null) {
            $payload->setMetadata('usage', (array) $event->usage);
        }
    }

    protected function streamEventType(object $event): ?string
    {
        $type = $event->type ?? null;

        if (! is_string($type) && method_exists($event, '__toString')) {
            $eventData = json_decode((string) $event, true);
            $type = is_array($eventData) ? ($eventData['type'] ?? null) : null;
        }

        return is_string($type) ? $type : null;
    }

    protected function persistUsage(ChatPayload $payload, string $conversationId): void
    {
        $usage = $payload->metadata['usage'] ?? null;

        if (! is_array($usage)) {
            return;
        }

        $inputTokens = (int) ($usage['promptTokens'] ?? $usage['prompt_tokens'] ?? 0);
        $outputTokens = (int) ($usage['completionTokens'] ?? $usage['completion_tokens'] ?? 0);

        AiUsageLog::create([
            'id' => (string) Str::uuid7(),
            'user_id' => $payload->user?->getAuthIdentifier(),
            'conversation_id' => $conversationId,
            'provider' => (string) config('ai-chat.provider', 'openai'),
            'model' => (string) config('ai-chat.model', 'unknown'),
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $inputTokens + $outputTokens,
            'cost' => 0,
            'latency_ms' => null,
            'status' => 'success',
            'error' => null,
        ]);
    }

    /**
     * Emit a single SSE frame and flush output buffers.
     *
     * @param  string  $field  SSE field name — usually "data" or "event"
     * @param  array<string, mixed>  $payload
     * @param  string|null  $event  Optional SSE event name (emits an "event:" line before "data:")
     */
    protected function emitSse(string $field, array $payload, ?string $event = null): void
    {
        if ($event !== null) {
            echo "event: {$event}\n";
        }

        echo "{$field}: ".json_encode($payload)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
