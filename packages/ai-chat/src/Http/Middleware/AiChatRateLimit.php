<?php

namespace AiChat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AiChatRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('ai-chat.rate_limiting.enabled', true)) {
            return $next($request);
        }

        $key = 'ai-chat:'.md5($request->ip());

        $maxRequests = config('ai-chat.rate_limiting.max_requests', 50);
        $decayMinutes = config('ai-chat.rate_limiting.decay_minutes', 60);

        if (RateLimiter::tooManyAttempts($key, $maxRequests)) {
            $retryAfter = RateLimiter::availableIn($key);

            return failResponse('Too many requests. Please try again later.', ['retry_after' => $retryAfter], 429)
                ->header('Retry-After', $retryAfter);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }
}
