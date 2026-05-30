<?php

namespace AiChat\Http\Controllers;

use AiChat\Agents\ChatAgent;
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
use AiChat\Storage\AnonymousConversationStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
        private readonly MessageManager $messageManager,
    ) {}

    public function sendMessage(SendMessageRequest $request): JsonResponse|StreamedResponse
    {
        try {
            $agent = $this->buildAgent($request);
            $message = $request->validated('message');

            if ($request->boolean('stream', false)) {
                return $this->streamAgentResponse($agent, $message);
            }

            $response = $agent->prompt($message);

            return successResponse([
                'conversation_id' => $agent->currentConversation(),
                'message' => new MessageResource($this->messageManager->storeAssistantMessage(
                    $agent->currentConversation(),
                    $response->text ?? '',
                    ['usage' => $response->usage ?? null, 'agent' => $agent->name()],
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

    public function streamMessage(SendMessageRequest $request): StreamedResponse
    {
        $agent = $this->buildAgent($request);

        return $this->streamAgentResponse($agent, $request->validated('message'));
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

    protected function streamAgentResponse(ChatAgent $agent, string $message): StreamedResponse
    {
        $conversationId = $agent->currentConversation();

        return new StreamedResponse(function () use ($agent, $message, $conversationId) {
            if ($conversationId) {
                echo "event: conversation_id\n";
                echo 'data: '.json_encode(['conversation_id' => $conversationId])."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            $stream = $agent->stream($message);

            foreach ($stream as $event) {
                echo 'data: '.((string) $event)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
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

    private function buildAgent(SendMessageRequest $request): ChatAgent
    {
        $agent = new ChatAgent($request->validated('system_prompt'));
        $conversationId = $request->validated('conversation_id');
        $sessionId = $request->validated('session_id');

        if ($conversationId) {
            $agent->continue($conversationId, $sessionId);
        } else {
            $agent->forSession($sessionId);

            $store = app(AnonymousConversationStore::class);
            $newId = $store->storeConversation($sessionId, 'New Chat');
            $agent->continue($newId, $sessionId);
        }

        return $agent;
    }
}
