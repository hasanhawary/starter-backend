<?php

namespace AiChat\Http\Controllers;

use AiChat\Agents\ChatAgent;
use AiChat\Chat\ConversationManager;
use AiChat\Chat\MessageManager;
use AiChat\Chat\StreamManager;
use AiChat\Http\Requests\FeedbackRequest;
use AiChat\Http\Requests\GetConversationRequest;
use AiChat\Http\Requests\ListConversationsRequest;
use AiChat\Http\Requests\SendMessageRequest;
use AiChat\Models\AiChatConversation;
use AiChat\Models\AiFeedback;
use AiChat\Pipeline\ChatPayload;
use AiChat\Pipeline\ChatPipeline;
use AiChat\Resources\ConversationResource;
use AiChat\Resources\FeedbackResource;
use AiChat\Resources\MessageResource;
use AiChat\Storage\AnonymousConversationStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
        private readonly MessageManager $messageManager,
        private readonly ChatPipeline $pipeline,
        private readonly StreamManager $streamManager,
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

        return successResponse([
            'conversation_id' => $conversationId,
            'message' => new MessageResource([
                'id' => null,
                'role' => 'assistant',
                'content' => $payload->response ?? '',
                'usage' => $payload->metadata['usage'] ?? null,
                'created_at' => now()->toIso8601String(),
                'tool_calls' => ! empty($payload->toolResults),
            ]),
            'plan' => $payload->executionPlan?->toArray() ?? null,
        ]);
    }

    public function streamMessage(SendMessageRequest $request): StreamedResponse
    {
        $payload = $this->buildPayload($request, streaming: true);

        $conversationId = $this->ensureConversation($request);

        if ($conversationId) {
            $payload->conversation = $this->conversationManager->find($conversationId);
        }

        $agent = $this->buildAgentForStream($request, $conversationId);

        $planPayload = (clone $payload);
        $planPayload = $this->pipeline->process($planPayload);

        $selectedToolNames = array_keys($planPayload->tools ?? []);

        if (! empty($selectedToolNames)) {
            $agent->withTools($selectedToolNames);
        }

        return new StreamedResponse(function () use ($agent, $request, $conversationId, $payload) {
            if ($conversationId) {
                echo "event: conversation_id\n";
                echo 'data: '.json_encode(['conversation_id' => $conversationId])."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            $message = $request->validated('message');

            $this->streamManager->startStream($conversationId ?? 'unknown');

            try {
                $stream = $agent->stream($message);

                $fullContent = '';

                foreach ($stream as $event) {
                    $content = (string) ($event->text ?? '');
                    $fullContent .= $content;

                    echo 'data: '.((string) $event)."\n\n";

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();

                    $this->streamManager->appendToStream($conversationId ?? 'unknown', $content);
                }

                $payload->response = $fullContent;

                $this->persistStreamResponse($payload, $conversationId);

                $this->streamManager->endStream($conversationId ?? 'unknown', $fullContent);
            } catch (\Throwable $e) {
                $this->streamManager->abortStream($conversationId ?? 'unknown');

                echo 'data: '.json_encode(['error' => $e->getMessage()])."\n\n";
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

    protected function buildAgentForStream(SendMessageRequest $request, ?string $conversationId): ChatAgent
    {
        $agent = new ChatAgent($request->validated('system_prompt'));
        $sessionId = $request->validated('session_id');

        if ($conversationId) {
            $agent->continue($conversationId, $sessionId);
        } else {
            $agent->forSession($sessionId);
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

            $this->messageManager->storeUserMessage(
                $conversationId,
                $payload->message,
                is_string($userId) ? $userId : (string) ($userId ?? 'anonymous'),
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
        } catch (\Throwable) {
        }
    }
}
