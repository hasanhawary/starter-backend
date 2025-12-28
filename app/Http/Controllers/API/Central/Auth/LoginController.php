<?php

namespace App\Http\Controllers\API\Auth;

use App\Exceptions\InactiveUserException;
use App\Exceptions\InvalidEmailAndPasswordCombinationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Auth\LoginRequest;
use App\Http\Resources\Central\Auth\LoginResource;
use App\Models\User;
use App\Services\Auth\LoginService;
use App\Services\Auth\ThrottleService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * @param LoginService $loginService
     * @param ThrottleService $throttleService
     */
    public function __construct(protected LoginService $loginService, protected ThrottleService $throttleService)
    {
    }

    /**
     * @param LoginRequest $request
     * @return JsonResponse
     * @throws InvalidEmailAndPasswordCombinationException
     * @throws InactiveUserException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $key = $this->throttleService->generateThrottleKey($request->email, $request->ip());
        $this->throttleService->ensureIsNotRateLimited($key, 5);

        try {
            $user = $this->loginService
                ->setGuard('api')
                ->setModel(User::class)
                ->attempt($request->validated());

            $this->throttleService->clearRateLimit($key);

            return successResponse(new LoginResource($user['user'], $user['token']), __('api.login_success'));

        } catch (InvalidEmailAndPasswordCombinationException $e) {
            $this->throttleService->incrementRateLimit($key, 400);
            throw $e;
        }
    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->user()->tokens()->where('id', auth()->user()->currentAccessToken()->id)->delete();

        return successResponse(msg: trans('api.user_logged_out'));
    }
}
