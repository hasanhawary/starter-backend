<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidOtpException;
use App\Http\Requests\Central\Auth\SendOtpRequest;
use App\Http\Requests\Central\Auth\VerifyOtpRequest;
use Carbon\Carbon;
use Random\RandomException;

class OTPService extends BaseAuthService
{
    /**
     * Send OTP to user for a specific purpose
     *
     * @param SendOtpRequest $request
     * @param string $type OTP type: login, reset_password, verify_email
     * @return string
     * @throws InvalidOtpException
     * @throws RandomException
     */
    public function send(SendOtpRequest $request, string $type = 'login'): string
    {
        $user = $this->resolveUser($request->email);

        // Prevent resending OTP too frequently
        if (!empty($user->otp_data[$type]['sent_at']) && config('project.otp.delay')) {
            $sentAt = Carbon::parse($user->otp_data[$type]['sent_at']);
            $delaySeconds = (int)config('project.otp.delay');

            $allowedAt = $sentAt->addSeconds($delaySeconds);

            if (now()->lessThan($allowedAt)) {
                $remainingSeconds = now()->diffInSeconds($allowedAt);

                throw new InvalidOtpException(__('api.otp_already_sent_wait', ['seconds' => round($remainingSeconds)]));
            }
        }

        // OTP defaults from project config
        $otpConfig = config('project.otp');
        $otp = $otpConfig['default'] ?? random_int(1000, 9999);
        $expiresIn = $otpConfig['expires_in'] ?? 10;

        $expireAt = now()->addMinutes($expiresIn);

        // Save OTP in array column
        $otpData = $user->otp_data ?? [];
        $otpData[$type] = [
            'otp' => (string)$otp,
            'sent_at' => now()->format('Y-m-d H:i:s'),
            'expires_at' => $expireAt->format('Y-m-d H:i:s'),
        ];

        $user->update(['otp_data' => $otpData]);

        // Send notification
        $user->sendNotification(
            $this->getOtpTemplates($type, $otp, $expireAt, $user->name) + ['otp' => $otp],
            ['email']
        );

        return (string)$otp;
    }

    /**
     * Verify OTP for a specific type
     *
     * @param VerifyOtpRequest $request
     * @param string $type
     * @return bool
     * @throws InvalidOtpException
     */
    public function verify(VerifyOtpRequest $request, string $type = 'login'): mixed
    {
        $user = $this->resolveUser($request->email);

        $otpData = $user->otp_data[$type] ?? null;

        if (!$otpData) {
            throw new InvalidOtpException(__('api.invalid_otp'));
        }

        if (Carbon::parse($otpData['expires_at'])->isPast()) {
            throw new InvalidOtpException(__('api.otp_expired'));
        }

        if ($otpData['otp'] !== (string)$request->otp) {
            throw new InvalidOtpException(__('api.invalid_otp'));
        }

        // Clear OTP for this type
        $otpArray = $user->otp_data;
        unset($otpArray[$type]);

        $update = ['otp_data' => $otpArray];
        if ($type === 'verify_email' && !$user->email_verified_at) {
            $update['email_verified_at'] = now();
        }

        $user->update($update);

        return $user;
    }

    /**
     * Check OTP without clearing it
     *
     * @param VerifyOtpRequest $request
     * @param string $type
     * @return bool
     * @throws InvalidOtpException
     */
    public function check(VerifyOtpRequest $request, string $type = 'login'): mixed
    {
        $user = $this->resolveUser($request->email);

        $otpData = $user->otp_data[$type] ?? null;

        if (!$otpData) {
            throw new InvalidOtpException(__('api.invalid_otp'));
        }

        if (Carbon::parse($otpData['expires_at'])->isPast()) {
            throw new InvalidOtpException(__('api.otp_expired'));
        }

        if ($otpData['otp'] !== (string)$request->otp) {
            throw new InvalidOtpException(__('api.invalid_otp'));
        }

        return $user;
    }

    /**
     * Get notification templates for each OTP type
     *
     * @param string $type
     * @param int $otp
     * @param Carbon $expireAt
     * @param string $userName
     * @return array
     */
    private function getOtpTemplates(string $type, int $otp, Carbon $expireAt, string $userName): array
    {
        $expire = $expireAt->format('h:i A');
        $platformName = config('mail.default_brand');

        return match ($type) {
            'login' => [
                'title' => 'login_otp_title',
                'msg' => "login_otp_msg|name={$userName}|expires_at={$expire}|otp={$otp}"
            ],
            'reset_password' => [
                'title' => 'reset_password_otp_title',
                'msg' => "reset_password_otp_msg|name={$userName}|expires_at={$expire}|otp={$otp}"
            ],
            'verify_email' => [
                'title' => 'verify_email_otp_title',
                'msg' => "verify_email_otp_msg|name={$userName}|expires_at={$expire}|otp={$otp}"
            ],
            default => [
                'title' => 'default_otp_title',
                'msg' => "default_otp_msg|name={$userName}|expires_at={$expire}|otp={$otp}"
            ],
        };
    }
}
