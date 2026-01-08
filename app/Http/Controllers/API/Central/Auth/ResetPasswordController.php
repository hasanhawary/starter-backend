<?php

namespace App\Http\Controllers\API\Central\Auth;

use App\Exceptions\EmailVerifiedException;
use App\Exceptions\InvalidOtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Auth\ResetPasswordRequest;
use App\Models\Central\Admin;
use App\Services\Auth\ResetPasswordService;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    public function __construct(protected ResetPasswordService $forgetPasswordService)
    {
    }

    /**
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     * @throws InvalidOtpException
     */
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $this->forgetPasswordService
            ->setModel(Admin::class)
            ->reset($request);

        return successResponse(msg: __('api.password_reset_success'));
    }
}
