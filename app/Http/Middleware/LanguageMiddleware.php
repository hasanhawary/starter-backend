<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    protected const array ALLOWED_LOCALIZATIONS = ['en', 'ar'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $localization = $request->header('Accept-Language');

        $localization = in_array($localization, self::ALLOWED_LOCALIZATIONS, true)
            ? $localization
            : 'ar';

        app()->setLocale($localization);

        return $next($request);
    }
}
