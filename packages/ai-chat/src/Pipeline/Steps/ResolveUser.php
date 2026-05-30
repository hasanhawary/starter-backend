<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Pipeline\ChatPayload;
use Closure;
use Illuminate\Support\Facades\Auth;

class ResolveUser
{
    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        if ($payload->user === null) {
            $payload->user = Auth::user();
        }

        return $next($payload);
    }
}
