<?php

namespace App\Http\Controllers\API\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Auth\ResetPasswordRequest;
use App\Services\Auth\ResetPasswordService;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    protected ResetPasswordService $forgetPasswordService;

    /**
     * @param ResetPasswordService $forgetPasswordService
     */
    public function __construct(ResetPasswordService $forgetPasswordService)
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
