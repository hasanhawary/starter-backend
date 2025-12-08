<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
        return new $this->model;
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
}
