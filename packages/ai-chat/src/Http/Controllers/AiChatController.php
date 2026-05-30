<?php

namespace AiChat\Http\Controllers;

use AiChat\Chat\ConversationManager;
use AiChat\Chat\MessageManager;
use AiChat\Http\Requests\FeedbackRequest;
use AiChat\Http\Requests\GetConversationRequest;
use AiChat\Http\Requests\ListConversationsRequest;
use AiChat\Http\Requests\SendMessageRequest;
use AiChat\Http\Resources\ConversationResource;
use AiChat\Http\Resources\FeedbackResource;
use AiChat\Http\Resources\MessageResource;
use AiChat\Models\AiChatConversation;
use AiChat\Models\AiFeedback;
use AiChat\Pipeline\ChatPayload;
use AiChat\Pipeline\ChatPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
        private readonly MessageManager $messageManager,
        private readonly ChatPipeline $pipeline,
    ) {}

    public function sendMessage(SendMessageRequest $request): JsonResponse|StreamedResponse
    {
        try {
            $payload = new ChatPayload(
                message: $request->validated('message'),
                user: $request->attributes->get('ai_chat_user'),
            );

            $payload->streaming = $request->boolean('stream', false);

            if ($conversationId = $request->validated('conversation_id')) {
                $payload->conversation = $this->conversationManager->get($conversationId);
            }

            $payload->setContext('session_id', $request->validated('session_id'));
            $payload->setContext('system_prompt', $request->validated('system_prompt'));
            $payload->setContext('agent', $request->validated('agent'));

            if ($payload->streaming) {
                return $this->streamResponse($payload);
            }

            $payload = $this->pipeline->process($payload);

            if ($payload->hasErrors()) {
                return failResponse($payload->firstError(), [], $payload->firstErrorCode());
            }

            return successResponse([
                'conversation_id' => $payload->conversationId(),
                'message' => new MessageResource($this->messageManager->storeAssistantMessage(
                    $payload->conversationId(),
                    $payload->response ?? '',
                    $payload->metadata,
                )),
            ]);
        } catch (\Throwable $e) {
            return failResponse($e->getMessage(), [], 500);
        }
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

        $deleted = $this->conversationManager->delete($id);

        if (! $deleted) {
            return failResponse('Conversation not found.', [], 404);
        }

        return successResponse(msg: 'Conversation deleted.');
    }

    public function streamMessage(SendMessageRequest $request): StreamedResponse
    {
        $payload = new ChatPayload(
            message: $request->validated('message'),
            user: $request->attributes->get('ai_chat_user'),
        );

        $payload->streaming = true;

        if ($conversationId = $request->validated('conversation_id')) {
            $payload->conversation = $this->conversationManager->get($conversationId);
        }

        $payload->setContext('session_id', $request->validated('session_id'));
        $payload->setContext('system_prompt', $request->validated('system_prompt'));
        $payload->setContext('agent', $request->validated('agent'));

        return $this->streamResponse($payload);
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

    protected function streamResponse(ChatPayload $payload): StreamedResponse
    {
        return new StreamedResponse(function () use ($payload) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            $payload = $this->pipeline->process($payload);

            if ($payload->hasErrors()) {
                echo "event: error\n";
                echo 'data: '.json_encode(['error' => $payload->firstError()])."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                return;
            }

            echo "event: conversation_id\n";
            echo 'data: '.json_encode(['conversation_id' => $payload->conversationId()])."\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            $fullContent = $payload->response ?? '';

            foreach (str_split($fullContent, 50) as $chunk) {
                echo "event: token\n";
                echo 'data: '.json_encode(['token' => $chunk])."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            echo "event: done\n";
            echo "data: {}\n\n";

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
}
