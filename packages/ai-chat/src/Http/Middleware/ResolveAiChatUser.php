<?php

namespace AiChat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAiChatUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('sanctum')) {
            $request->attributes->set('ai_chat_user', $request->user('sanctum'));
            $request->attributes->set('ai_chat_session_id', (string) $request->user('sanctum')->getAuthIdentifier());
        } else {
            $sessionId = $request->input('session_id')
                ?? $request->query('session_id');

            if ($sessionId) {
                $request->attributes->set('ai_chat_user', null);
                $request->attributes->set('ai_chat_session_id', $sessionId);
            }
        }

        return $next($request);
    }
}
