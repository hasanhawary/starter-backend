<?php

namespace App\Services\Auth;

use App\Enum\Global\OtpTypeEnum;
use App\Exceptions\InvalidOtpException;
use App\Http\Requests\Global\Auth\ResetPasswordRequest;
use App\Http\Requests\Global\Auth\VerifyOtpRequest;

class ResetPasswordService extends BaseAuthService
{
    public function __construct(protected OTPService $otpService)
    {
    }

    /**
     * Reset user password using OTP
     *
     * @param ResetPasswordRequest $request
     * @return bool
     * @throws InvalidOtpException
     */
    public function reset(ResetPasswordRequest $request): bool
    {
        // Use OTPService check method
        $user = $this->otpService
            ->setModel($this->model)
            ->check(new VerifyOtpRequest($request->validated()), OtpTypeEnum::ResetPassword->value);

        if (!$user) {
            throw new InvalidOtpException(__('api.invalid_otp'));
        }

        // Update password and clear only reset_password OTP
        $user->password = $request->password;
        $otpData = $user->otp_data ?? [];
        unset($otpData[OtpTypeEnum::ResetPassword->value]);
        $user->otp_data = $otpData;
        $user->save();

        return true;
    }
}
