<?php

namespace App\Http\Controllers\API\Global\Auth;

use App\Exceptions\EmailVerifiedException;
use App\Exceptions\InActiveUserException;
use App\Exceptions\InvalidEmailAndPasswordCombinationException;
use App\Exceptions\InvalidOtpException;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Auth\LoginRequest;
use App\Http\Resources\Global\Auth\LoginResource;
use App\Models\Admin;
use App\Services\Auth\LoginService;
use App\Services\Auth\ThrottleService;
use Illuminate\Http\JsonResponse;

class LoginController extends BaseController
{
    public function __construct(
        protected LoginService    $loginService,
        protected ThrottleService $throttleService
    )
    {
        parent::__construct();
    }

    /**
     * Handle admin login
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $key = $this->throttleService->generateThrottleKey($request->email, $request->ip());
        $this->throttleService->ensureIsNotRateLimited($key, config('project.auth.max_login_attempts'));

        try {
            $userData = $this->loginService
                ->setModel($this->userModel)
                ->setGuard($this->guard)
                ->attempt($request->validated());

            $this->throttleService->clearRateLimit($key);

            return successResponse(
                new LoginResource($userData['user'], $userData['token']),
                __('api.login_success')
            );

        } catch (InvalidEmailAndPasswordCombinationException|InActiveUserException|InvalidOtpException $e) {
            $this->throttleService->incrementRateLimit($key, config('project.auth.lockout_time'));
            return failResponse($e->getMessage());
        } catch (EmailVerifiedException $e) {

            return failResponse($e->getMessage(), code: 403);
        }
    }
}
