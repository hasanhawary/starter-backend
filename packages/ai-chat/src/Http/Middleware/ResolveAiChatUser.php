<?php

namespace AiChat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
                ?? $request->query('session_id')
                ?? (string) Str::uuid();

            $request->attributes->set('ai_chat_user', null);
            $request->attributes->set('ai_chat_session_id', $sessionId);

            if (! $request->has('session_id') && ! $request->query->has('session_id')) {
                $request->merge(['session_id' => $sessionId]);
            }
        }

        return $next($request);
    }
}
