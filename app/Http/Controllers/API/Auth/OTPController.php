<?php

namespace App\Http\Controllers\API\Auth;

use App\Exceptions\InvalidOtpException;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\OTPService;
use Illuminate\Http\JsonResponse;
use Random\RandomException;

class OTPController extends BaseController
{
    public function __construct(protected OTPService $otpService)
    {
        parent::__construct();
    }

    /**
     * Send OTP
     *
     * @throws InvalidOtpException
     * @throws RandomException
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $this->otpService
            ->setModel($this->userModel)
            ->send($request, $request->type);

        return successResponse(msg: __('api.otp_sent'));
    }

    /**
     * Verify OTP
     *
     * @throws InvalidOtpException
     */
    public function check(VerifyOtpRequest $request): JsonResponse
    {
        $this->otpService
            ->setModel($this->userModel)
            ->check($request, $request->type);

        return successResponse(msg: __('api.otp_verified'));
    }

    /**
     * Verify OTP
     *
     * @throws InvalidOtpException
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $this->otpService
            ->setModel($this->userModel)
            ->verify($request, $request->type);

        return successResponse(msg: __('api.otp_verified'));
    }
}
