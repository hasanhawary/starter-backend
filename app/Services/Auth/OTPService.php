<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidOtpException;
use App\Http\Requests\Auth\SendOtpRequest;
use Carbon\Carbon;
use Random\RandomException;

class OTPService extends BaseAuthService
{
    /**
     * Send an OTP to the user for a specific purpose (login, reset password, verify email).
     *
     * - Respects resend delay
     * - Generates OTP based on config
     * - Stores OTP metadata (expiry, attempts, lock)
     * - Sends notification
     *
     *
     * @throws InvalidOtpException
     * @throws RandomException
     */
    public function send(SendOtpRequest $request, string $type = 'login'): string
    {
        $user = $this->resolveUser($request->email);

        $this->ensureOtpNotRecentlySent($user, $type);

        $otpConfig = config('project.otp');

        $otp = $otpConfig['default'] ?? $this->generateOtp();

        $expiresIn = $otpConfig['expires_in'] ?? 10;
        $expireAt = now()->addMinutes($expiresIn);

        $otpData = $user->otp_data ?? [];

        $otpData[$type] = [
            'otp' => (string) $otp,
            'sent_at' => now()->toDateTimeString(),
            'expires_at' => $expireAt->toDateTimeString(),
            'attempts' => [],
            'locked_until' => [],
        ];

        $user->update(['otp_data' => $otpData]);

        $user->sendNotification(
            $this->getOtpTemplates($type, (int) $otp, $expireAt, $user->name) + ['otp' => $otp],
            ['email']
        );

        return (string) $otp;
    }

    /**
     * Verify an OTP and Reset it after successful validation.
     *
     * - Clears OTP data
     * - Marks email as verified when type is "verify_email"
     *
     * @param
     *  $request
     *
     * @throws InvalidOtpException
     */
    public function verify(
        mixed $request, string $type = 'login'): mixed
    {
        $user = $this->validateOtp($request, $type);

        $this->resetOtp($user, $type);

        if ($type === 'verify_email' && ! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        return $user;
    }

    /**
     * Validate an OTP without consuming or deleting it.
     *
     * Useful for multistep verification flows.
     *
     * @param
     *  $request
     *
     * @throws InvalidOtpException
     */
    public function check(
        mixed $request, string $type = 'login'): mixed
    {
        return $this->validateOtp($request, $type);
    }

    /**
     * Validate OTP existence, lock status, expiry, and value.
     *
     * - Checks lock timeout
     * - Checks expiration
     * - Increments attempts on failure
     *
     * @param
     *  $request
     *
     * @throws InvalidOtpException
     */
    private function validateOtp(
        mixed $request, string $type): mixed
    {
        $user = $this->resolveUser($request->email);
        $otpData = $user->otp_data[$type] ?? null;

        if (! $otpData) {
            throw new InvalidOtpException(__('api.invalid_otp'));
        }

        $ip = request()->ip();
        if (! empty($otpData['locked_until'][$ip]) &&
            now()->lessThan(Carbon::parse($otpData['locked_until'][$ip]))
        ) {
            $seconds = now()->diffInSeconds(Carbon::parse($otpData['locked_until'][$ip]));
            throw new InvalidOtpException(__('api.otp_locked_try_later', ['seconds' => round($seconds)]));
        }

        if (Carbon::parse($otpData['expires_at'])->isPast()) {
            throw new InvalidOtpException(__('api.otp_expired'));
        }

        if ($otpData['otp'] !== (string) $request->otp) {
            $this->incrementAttempts($user, $type, request()->ip());
            throw new InvalidOtpException(__('api.invalid_otp'));
        }

        return $user;
    }

    /**
     * Reset (remove) OTP data after successful verification.
     *
     * @param  mixed  $user
     */
    private function resetOtp($user, string $type): void
    {
        $otpData = $user->otp_data;
        unset($otpData[$type]);

        $user->update(['otp_data' => $otpData]);
    }

    /**
     * Prevent sending OTP again within the configured delay window.
     *
     * @param  mixed  $user
     *
     * @throws InvalidOtpException
     */
    private function ensureOtpNotRecentlySent($user, string $type): void
    {
        if (empty($user->otp_data[$type]['sent_at']) || ! config('project.otp.delay')) {
            return;
        }

        $sentAt = Carbon::parse($user->otp_data[$type]['sent_at']);
        $delaySeconds = (int) config('project.otp.delay');

        if (now()->lessThan($sentAt->addSeconds($delaySeconds))) {
            $remaining = now()->diffInSeconds($sentAt->addSeconds($delaySeconds));
            throw new InvalidOtpException(__('api.otp_already_sent_wait', ['seconds' => round($remaining)]));
        }
    }

    /**
     * Increment OTP verification attempts and lock OTP if limit is exceeded.
     *
     * @param  mixed  $user
     */
    private function incrementAttempts($user, string $type, string $ip): void
    {
        $config = config('project.otp');

        $maxAttempts = (int) ($config['max_attempts'] ?? 5);
        $lockSeconds = (int) ($config['lock_time'] ?? 120);

        $otpData = $user->otp_data;

        $otpData[$type]['attempts'][$ip] =
            ($otpData[$type]['attempts'][$ip] ?? 0) + 1;

        if ($otpData[$type]['attempts'][$ip] >= $maxAttempts) {
            $otpData[$type]['locked_until'][$ip] = now()
                ->addSeconds($lockSeconds)
                ->toDateTimeString();
        }

        $user->update(['otp_data' => $otpData]);
    }

    /**
     * Generate OTP based on configuration (length & type).
     *
     *
     * @throws RandomException
     */
    private function generateOtp(): string
    {
        $config = config('project.otp');

        $length = (int) ($config['length'] ?? 4);
        $type = $config['type'] ?? 'numeric';

        return match ($type) {
            'alpha' => $this->randomString('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $length),
            'alphanumeric' => $this->randomString('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $length),
            default => $this->randomNumeric($length),
        };
    }

    /**
     * Generate a numeric OTP of fixed length.
     *
     *
     * @throws RandomException
     */
    private function randomNumeric(int $length): string
    {
        $min = 10 ** ($length - 1);
        $max = (10 ** $length) - 1;

        return (string) random_int($min, $max);
    }

    /**
     * Generate a random OTP string from a character set.
     *
     *
     * @throws RandomException
     */
    private function randomString(string $characters, int $length): string
    {
        $result = '';
        $maxIndex = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $maxIndex)];
        }

        return $result;
    }

    /**
     * Build OTP notification templates based on OTP type.
     */
    private function getOtpTemplates(string $type, int $otp, Carbon $expireAt, string $userName): array
    {
        $expire = $expireAt->format('h:i A');

        return match ($type) {
            'login' => [
                'title' => 'login_otp_title',
                'msg' => "login_otp_msg|name={$userName}|expires_at={$expire}|otp={$otp}",
            ],
            'reset_password' => [
                'title' => 'reset_password_otp_title',
                'msg' => "reset_password_otp_msg|name={$userName}|expires_at={$expire}|otp={$otp}",
            ],
            'verify_email' => [
                'title' => 'verify_email_otp_title',
                'msg' => "verify_email_otp_msg|name={$userName}|expires_at={$expire}|otp={$otp}",
            ],
            default => [
                'title' => 'default_otp_title',
                'msg' => "default_otp_msg|name={$userName}|expires_at={$expire}|otp={$otp}",
            ],
        };
    }
}
