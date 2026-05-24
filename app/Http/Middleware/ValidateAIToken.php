<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAIToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->header('X-Token') !== config('token.aiToken')) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
