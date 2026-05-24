<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;

abstract class BaseController
{
    protected ?string $guard;

    protected ?string $userModel;

    public function __construct()
    {
        $this->guard = config('auth.defaults.guard');
        Auth::shouldUse($this->guard);

        $provider = config("auth.guards.{$this->guard}.provider");
        $this->userModel = config("auth.providers.$provider.model");
    }
}
