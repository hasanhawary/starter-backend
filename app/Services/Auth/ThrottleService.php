<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ThrottleService
{
    public function ensureIsNotRateLimited(string $key, int $maxAttempts = 3): void
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => [__('auth.throttle', [
                'seconds' => RateLimiter::availableIn($key),
            ])],
        ]);
    }

    public function incrementRateLimit(string $key, int $decaySeconds = 400): void
    {
        RateLimiter::hit($key, $decaySeconds);
    }

    public function clearRateLimit(string $key): void
    {
        RateLimiter::clear($key);
    }

    public function generateThrottleKey(string $email, string $ip): string
    {
        return Str::lower($email).'|'.$ip;
    }
}
