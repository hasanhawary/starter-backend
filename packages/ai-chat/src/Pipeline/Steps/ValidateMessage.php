<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Pipeline\ChatPayload;
use Closure;
use Illuminate\Support\Facades\RateLimiter;

class ValidateMessage
{
    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $message = trim($payload->message);

        if ($message === '') {
            $payload->addError('Message cannot be empty.', 422);

            return $payload;
        }

        $maxLength = (int) config('ai-chat.conversations.max_message_length', 10000);

        if (mb_strlen($message) > $maxLength) {
            $payload->addError("Message exceeds maximum length of {$maxLength} characters.", 422);

            return $payload;
        }

        if ($this->isRateLimited($payload)) {
            $payload->addError('Rate limit exceeded. Please try again later.', 429);

            return $payload;
        }

        return $next($payload);
    }

    protected function isRateLimited(ChatPayload $payload): bool
    {
        if (! config('ai-chat.rate_limiting.enabled', true)) {
            return false;
        }

        $userId = $payload->user?->getAuthIdentifier();

        $key = $userId
            ? 'ai-chat:msg:'.$userId
            : 'ai-chat:msg:ip:'.request()->ip();

        $maxRequests = (int) config('ai-chat.rate_limiting.max_requests', 50);
        $decaySeconds = (int) config('ai-chat.rate_limiting.decay_minutes', 60) * 60;

        if (RateLimiter::tooManyAttempts($key, $maxRequests)) {
            return true;
        }

        RateLimiter::hit($key, $decaySeconds);

        return false;
    }
}
