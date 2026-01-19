<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;

abstract class BaseController
{
    protected string $guard;
    protected string $userModel;

    public function __construct()
    {
        $this->guard = $this->detectGuard();
        Auth::shouldUse($this->guard);
        $this->userModel = $this->getAuthModel($this->guard);
    }

    /**
     * Detect which guard to use.
     * Here you can customize logic based on request, route prefix, or header
     */
    protected function detectGuard(): string
    {
        return detectGuard();
    }

    /**
     * Get the user model class associated with a guard
     */
    protected function getAuthModel(?string $guard = null): string
    {
        return getAuthModel($guard);
    }

    /**
     * Get current authenticated user model class
     */
    protected function currentUserModel(): string
    {
        return $this->userModel;
    }

    /**
     * Get the current authenticated user instance
     */
    protected function currentUser()
    {
        return Auth::user();
    }
}
