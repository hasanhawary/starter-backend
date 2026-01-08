<?php

namespace App\Http\Controllers\API\Central\Auth;

use App\Exceptions\EmailVerifiedException;
use App\Exceptions\InActiveUserException;
use App\Exceptions\InvalidEmailAndPasswordCombinationException;
use App\Exceptions\InvalidOtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Auth\LoginRequest;
use App\Http\Resources\Central\Auth\LoginResource;
use App\Models\Central\Admin;
use App\Services\Auth\LoginService;
use App\Services\Auth\ThrottleService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        protected LoginService $loginService,
        protected ThrottleService $throttleService
    ) {}

    /**
     * Handle admin login
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $key = $this->throttleService->generateThrottleKey($request->email, $request->ip());
        $this->throttleService->ensureIsNotRateLimited($key, config('project.throttle.login'));

        try {
            $userData = $this->loginService
                ->setModel(Admin::class)
                ->setGuard('admin')
                ->attempt($request->validated());

            $this->throttleService->clearRateLimit($key);

            return successResponse(
                new LoginResource($userData['user'], $userData['token']),
                __('api.login_success')
            );

        } catch (InvalidEmailAndPasswordCombinationException|InActiveUserException|InvalidOtpException $e) {
            $this->throttleService->incrementRateLimit($key, 400);
            return failResponse(msg: $e->getMessage());
        } catch (EmailVerifiedException $e) {

            return failResponse(msg: $e->getMessage(), code: 403);
        }
    }
}
