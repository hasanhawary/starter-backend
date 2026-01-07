<?php
namespace App\Guards;

use Laravel\Sanctum\Guard as BaseSanctumGuard;
use Laravel\Sanctum\Sanctum;

class SanctumGuard extends BaseSanctumGuard
{
    /**
     * Determine if the provided access token is valid.
     *
     * @param  mixed  $accessToken
     * @return bool
     */
    protected function isValidAccessToken($accessToken): bool
    {
        if (! $accessToken) {
            return false;
        }

        $last_used_at = $accessToken->last_used_at;
        if(!$last_used_at) {
            $last_used_at = $accessToken->created_at;
        }

        $isValid =
            (!$this->expiration || $last_used_at->gt(now()->subMinutes($this->expiration)));
//            && $this->hasValidProvider($accessToken->tokenable);

        if (is_callable(Sanctum::$accessTokenAuthenticationCallback)) {
            $isValid = (bool) (Sanctum::$accessTokenAuthenticationCallback)($accessToken, $isValid);
        }

        return $isValid;
    }
}
