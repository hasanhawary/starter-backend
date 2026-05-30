<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Pipeline\ChatPayload;
use Closure;

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

        return $next($payload);
    }
}
