<?php

namespace App\Http\Controllers\API\Auth;

use App\Exceptions\AccountNotFoundException;
use App\Exceptions\InvalidPasswordResetTokenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\ForgetPasswordService;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    protected ForgetPasswordService $forgetPasswordService;

    /**
     * @param ForgetPasswordService $forgetPasswordService
     */
    public function __construct(ForgetPasswordService $forgetPasswordService)
    {
        $this->forgetPasswordService = $forgetPasswordService;
    }

    /**
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $isPasswordReset = $this->forgetPasswordService->reset($request);

        if (!$isPasswordReset) {
            return failResponse(msg: __('api.invalid_otp_or_email'));
        }

        return successResponse(msg: __('api.password_reset_success'));
    }
}
