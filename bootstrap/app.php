<?php

use App\Exceptions\AccountNotFoundException;
use App\Exceptions\InvalidEmailAndPasswordCombinationException;
use App\Exceptions\InvalidPasswordResetTokenException;
use App\Exceptions\ModelAlreadyExistsException;
use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            LanguageMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return failResponse(msg: __('api.record_not_found'), code: $e->getStatusCode());
            }
        });

        $exceptions->render(function (InvalidPasswordResetTokenException $e) {
            if (request()->acceptsJson()) {
                return failResponse(msg: $e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (AccountNotFoundException $e) {
            if (request()->acceptsJson()) {
                return failResponse(msg: $e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (UnauthorizedException $e) {
            if (request()->acceptsJson()) {
                return failResponse(msg: 'Unauthorized', code: 403);
            }
        });

        $exceptions->render(function (InvalidEmailAndPasswordCombinationException $e) {
            if (request()->acceptsJson()) {
                return failResponse(msg: $e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (InvalidEmailAndPasswordCombinationException $e) {
            if (request()->acceptsJson()) {
                return failResponse(msg: $e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (ModelAlreadyExistsException $e) {
            if (request()->acceptsJson()) {
                return failResponse(data: $e->getData(), msg: $e->getMessage(), code: $e->getCode());
            }
        });

    })->create();
