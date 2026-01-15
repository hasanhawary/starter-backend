<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidOtpException;
use App\Models\Central\Admin;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Tenant;

abstract class BaseAuthService
{
    protected string $model;
    protected string $guard;

    /**
     * @param string $model
     * @return $this
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return Model
     */
    public function getModel(): Model
    {
        if($this->model === Admin::class){
            Tenant::forgetCurrent();
        }

        return new $this->model();
    }

    /**
     * @param string $guard
     * @return $this
     */
    public function setGuard(string $guard): self
    {
        $this->guard = $guard;
        return $this;
    }

    /**
     * @return string
     */
    public function getGuard(): string
    {
        return $this->guard;
    }

    /**
     * @throws InvalidOtpException
     */
    public function resolveUser(string $email): mixed
    {
        $user = $this->getModel()
            ->query()
            ->where('email', $email)
            ->first();

        if (!$user) {
            throw new InvalidOtpException(__('api.email_not_registered'));
        }

        return $user;
    }
}
