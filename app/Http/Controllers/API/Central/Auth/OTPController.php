<?php

namespace App\Http\Controllers\API\Central\Auth;

use App\Exceptions\InvalidOtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Auth\SendOtpRequest;
use App\Http\Requests\Central\Auth\VerifyOtpRequest;
use App\Models\Central\Admin;
use App\Services\Auth\OTPService;
use Illuminate\Http\JsonResponse;

class OTPController extends Controller
{
    public function __construct(protected OTPService $otpService)
    {
    }

    /**
     * Send OTP
     *
     * @param SendOtpRequest $request
     * @return JsonResponse
     * @throws InvalidOtpException
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $this->otpService
            ->setModel(Admin::class)
            ->send($request, $request->type);

        return successResponse(msg: __('api.otp_sent'));
    }

    /**
     * Verify OTP
     *
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     * @throws InvalidOtpException
     */
    public function check(VerifyOtpRequest $request): JsonResponse
    {
        $this->otpService
            ->setModel(Admin::class)
            ->check($request, $request->type);

        return successResponse(msg: __('api.otp_verified'));
    }

    /**
     * Verify OTP
     *
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     * @throws InvalidOtpException
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $this->otpService
            ->setModel(Admin::class)
            ->verify($request, $request->type);

        return successResponse(msg: __('api.otp_verified'));
    }
}
