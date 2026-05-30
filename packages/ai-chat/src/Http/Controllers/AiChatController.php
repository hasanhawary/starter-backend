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
use AiChat\Storage\AnonymousConversationStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
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
            'message' => [
                'id' => null,
                'role' => 'assistant',
                'content' => $payload->response ?? '',
                'usage' => $payload->metadata['usage'] ?? null,
                'created_at' => now()->toIso8601String(),
                'tool_calls' => ! empty($payload->toolResults),
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
        $systemPrompt = $this->buildSystemPrompt($planningPayload);

        $agent = $this->buildChatAgent($request, $conversationId, $selectedToolNames, $systemPrompt, $planningPayload->executionPlan);

        $finalConversationId = $conversationId;

        return new StreamedResponse(function () use ($agent, $request, $finalConversationId, $payload) {
            if ($finalConversationId) {
                echo "event: conversation_id\n";
                echo 'data: '.json_encode(['conversation_id' => $finalConversationId])."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            $message = $request->validated('message');

            $this->streamManager->startStream($finalConversationId ?? 'unknown');

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

                    $this->streamManager->appendToStream($finalConversationId ?? 'unknown', $content);
                }

                $payload->response = $fullContent;

                $this->persistStreamResponse($payload, $finalConversationId);

                $this->streamManager->endStream($finalConversationId ?? 'unknown', $fullContent);
            } catch (\Throwable $e) {
                $this->streamManager->abortStream($finalConversationId ?? 'unknown');

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

    protected function buildSystemPrompt(ChatPayload $payload): string
    {
        $parts = [];
        $agentPrompt = $payload->agent?->systemPrompt() ?? config('ai-chat.conversations.default_system_prompt', '');
        $plan = $payload->executionPlan;

        if ($agentPrompt !== '') {
            $parts[] = $agentPrompt;
        }

        $policy = null;
        $historyLabel = '';
        if ($plan) {
            $selector = app(HistorySelector::class);
            $policy = $selector->select($plan, $payload->message);
            $historyLabel = $selector->buildHistoryLabel($policy);
        }

        if ($policy) {
            if (! $policy->useHistory) {
                $parts[] = "\n\nThis is a greeting, identity statement, or casual message. Reply naturally and briefly to the current message only. Do not summarize, repeat, or answer previous unrelated questions unless the user explicitly asks.";
            } elseif ($policy->mode === 'summary') {
                $parts[] = "\n\nThe user is asking for a conversation summary. Use the conversation history to provide a summary of all topics discussed. Do not re-answer any individual questions.";
            } elseif ($policy->mode === 'relevant') {
                $parts[] = "\n\nOnly the most relevant previous context is provided. Use it only if the user's latest message requires it. Do not re-answer previous questions or mention unrelated topics.";
            } elseif ($plan) {
                if ($plan->isSimpleLiveData()) {
                    $parts[] = "\n\nUse the available tools to answer this live-data question. Do not invent values.";
                } elseif ($plan->isKnowledgeRequest()) {
                    $parts[] = "\n\nAnswer only from retrieved project knowledge. If missing, say you do not have enough information.";
                } elseif ($plan->isMemoryRequest()) {
                    $parts[] = "\n\nUse the conversation memory context to provide a relevant response.";
                }
            }
        }

        if ($historyLabel !== '') {
            $parts[] = "\n\n{$historyLabel}";
        }

        if (! empty($payload->context)) {
            $parts[] = "\n\n## Context\n".json_encode($payload->context, JSON_PRETTY_PRINT);
        }

        if (! empty($payload->knowledge)) {
            $parts[] = "\n\n## Knowledge Base\n".collect($payload->knowledge)
                ->map(fn ($k, $i) => '['.($i + 1).'] '.(is_array($k) ? json_encode($k) : (string) $k))
                ->implode("\n");
        }

        if (! empty($payload->memory)) {
            $parts[] = "\n\n## Conversation Memory\n".collect($payload->memory)
                ->map(fn ($m) => is_array($m) ? json_encode($m) : (string) $m)
                ->implode("\n");
        }

        return implode('', $parts);
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
