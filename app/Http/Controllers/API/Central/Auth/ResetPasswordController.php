<?php

namespace App\Http\Controllers\API\Central\Auth;

use App\Exceptions\InvalidOtpException;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Central\Auth\ResetPasswordRequest;
use App\Services\Auth\ResetPasswordService;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends BaseController
{
    public function __construct(protected ResetPasswordService $forgetPasswordService)
    {
        parent::__construct();
    }

    /**
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     * @throws InvalidOtpException
     */
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $this->forgetPasswordService
            ->setModel($this->userModel)
            ->reset($request);

        return successResponse(msg: __('api.password_reset_success'));
    }
}
