<?php

use App\Exceptions\AccountNotFoundException;
use App\Exceptions\InActiveUserException;
use App\Exceptions\InvalidEmailAndPasswordCombinationException;
use App\Exceptions\InvalidOtpException;
use App\Exceptions\InvalidPasswordResetTokenException;
use App\Exceptions\ModelAlreadyExistsException;
use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LdapRecord\Auth\BindException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [LanguageMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return failResponse(__('api.record_not_found'), code: $e->getStatusCode());
            }
        });

        $exceptions->render(function (InvalidPasswordResetTokenException $e) {
            if (request()->acceptsJson()) {
                return failResponse($e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (AccountNotFoundException $e) {
            if (request()->acceptsJson()) {
                return failResponse($e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (UnauthorizedException $e) {
            if (request()->acceptsJson()) {
                return failResponse(__('api.unauthorized'), code: 403);
            }
        });

        /*
         * A refusal that explains itself keeps its own message: policies answering
         * with Response::deny(...) and abort(403, ...) guards both land here, and
         * their reason is what the user needs to read. A bare `false` carries no
         * message — and Laravel's own English default is not one either — so those
         * still answer with the generic line.
         */
        $denialMessage = static function (?string $message): string {
            return blank($message) || $message === 'This action is unauthorized.'
                ? __('api.unauthorized')
                : $message;
        };

        $exceptions->render(function (AuthorizationException $e) use ($denialMessage) {
            if (request()->acceptsJson()) {
                return failResponse($denialMessage($e->response()?->message() ?? $e->getMessage()), code: 403);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e) use ($denialMessage) {
            if (request()->acceptsJson()) {
                return failResponse($denialMessage($e->getMessage()), code: 403);
            }
        });

        $exceptions->render(function (PermissionDoesNotExist $e) {
            if (request()->acceptsJson()) {
                return failResponse(__('api.permission_not_found'), code: 403);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e) {
            if (request()->acceptsJson()) {
                return failResponse(__('api.action_not_available'), code: 405);
            }
        });

        // An LDAP bind refuses with code 49 for bad credentials; every other
        // bind failure is an infrastructure problem and keeps its own message.
        $exceptions->render(function (BindException $e) {
            if (request()->acceptsJson()) {
                $message = match ($e->getCode()) {
                    49 => __('api.invalid_credentials'),
                    default => $e->getMessage(),
                };

                return failResponse($message, code: 401);
            }
        });

        $exceptions->render(function (InvalidEmailAndPasswordCombinationException $e) {
            if (request()->acceptsJson()) {
                return failResponse($e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (ModelAlreadyExistsException $e) {
            if (request()->acceptsJson()) {
                return failResponse(data: $e->getData(), msg: $e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (InvalidOtpException $e) {
            if (request()->acceptsJson()) {
                return failResponse($e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (InActiveUserException $e) {
            if (request()->acceptsJson()) {
                return failResponse($e->getMessage(), code: $e->getCode());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->acceptsJson()) {
                return failResponse(__('auth.unauthenticated'), code: 401);
            }
        });

        // Laravel appends "(and N more errors)" to the summary message; the
        // errors bag already carries them, so the client would render the count
        // twice.
        $exceptions->render(function (ValidationException $e) {
            if (request()->acceptsJson()) {
                $message = preg_replace('/ \(and \d+ more errors?\)/', '', $e->getMessage());

                return response()->json([
                    'message' => $message,
                    'errors' => $e->errors(),
                ], $e->status);
            }
        });
    })->create();

$app->useLangPath(base_path('lang'));

return $app;
