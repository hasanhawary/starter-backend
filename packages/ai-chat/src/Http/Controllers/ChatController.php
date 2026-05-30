<?php

namespace AiChat\Http\Controllers;

use AiChat\Agents\ChatAgent;
use AiChat\Http\Requests\GetConversationRequest;
use AiChat\Http\Requests\ListConversationsRequest;
use AiChat\Http\Requests\SendMessageRequest;
use AiChat\Http\Resources\ConversationResource;
use AiChat\Http\Resources\MessageResource;
use AiChat\Services\ChatService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Ai\Responses\StreamableAgentResponse;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function sendMessage(SendMessageRequest $request): JsonResponse|StreamableAgentResponse
    {
        try {
            $agent = $this->buildAgent($request);
            $message = $request->validated('message');

            if ($request->boolean('stream', true)) {
                return $agent->stream($message);
            }

            return successResponse([
                'question' => $message,
                'answer' => (string) $agent->prompt($message),
                'conversation_id' => $agent->currentConversation(),
            ]);
        } catch (RequestException $e) {
            $status = $e->response?->status() ?? 500;
            $body = $e->response?->json();

            return failResponse(
                $body['error']['message'] ?? $e->getMessage(),
                [],
                $status >= 400 && $status < 500 ? $status : 500,
            );
        } catch (\Throwable $e) {
            return failResponse(
                $e->getMessage(),
                [],
                500,
            );
        }
    }

    public function listConversations(ListConversationsRequest $request): JsonResponse
    {
        return successResponse($this->chatService->listConversations($request->validated()));
    }

    public function getConversation(string $id, GetConversationRequest $request): JsonResponse
    {
        $conversation = $this->chatService->getConversation($id, $request->validated('session_id'));

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
        $deleted = $this->chatService->deleteConversation($id, $request->validated('session_id'));

        if (! $deleted) {
            return failResponse('Conversation not found.', [], 404);
        }

        return successResponse(msg: 'Conversation deleted.');
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
        }

        return $agent;
    }
}
