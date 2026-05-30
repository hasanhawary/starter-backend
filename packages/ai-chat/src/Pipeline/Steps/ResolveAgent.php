<?php

namespace AiChat\Pipeline\Steps;

use AiChat\Agents\AgentResolver;
use AiChat\Pipeline\ChatPayload;
use Closure;
use Illuminate\Http\Request;

class ResolveAgent
{
    public function __construct(
        protected AgentResolver $resolver,
    ) {}

    public function handle(ChatPayload $payload, Closure $next): ChatPayload
    {
        $request = app(Request::class);

        $payload->agent = $this->resolver->resolveFromRequest($request);

        return $next($payload);
    }
}
