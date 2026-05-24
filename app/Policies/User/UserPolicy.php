<?php

namespace App\Policies\User;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(Authenticatable $user, ?User $model = null): bool
    {
        return $this->canAny($user, $model, [
            'view-all-user',
            'view-own-user',
        ]);
    }

    public function create(Authenticatable $user, ?User $model = null): bool
    {
        return $user->can('create-user') && $this->ownsOrAll($user, $model);
    }

    public function update(Authenticatable $user, User $model): bool
    {
        if (! $user->can('update-user')) {
            return false;
        }

        if ($this->isProtectedUser($model, $user)) {
            return false;
        }

        return $this->ownsOrAll($user, $model);
    }

    public function delete(Authenticatable $user, ?User $model = null): bool
    {
        if (! $user->can('delete-user')) {
            return false;
        }

        if ($model && $this->isProtectedUser($model, $user)) {
            return false;
        }

        return $this->ownsOrAll($user, $model);
    }

    public function restore(Authenticatable $user, ?User $model = null): bool
    {
        return $user->can('restore-user') && $this->ownsOrAll($user, $model);
    }

    public function forceDelete(Authenticatable $user, User $model): bool
    {
        if (! $user->can('force-delete-user')) {
            return false;
        }

        return ! $this->isProtectedUser($model, $user) && $this->ownsOrAll($user, $model);
    }

    public function toggleActive(Authenticatable $user, User $model): bool
    {
        if (! $user->can('toggle-active-user')) {
            return false;
        }

        return ! $this->isProtectedUser($model, $user) && $this->ownsOrAll($user, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(Authenticatable $user, ?User $model): bool
    {
        return ! $model
            || $user->can('view-all-user')
            || $model->created_by === $user->id;
    }

    protected function isProtectedUser(User $model, Authenticatable $user): bool
    {
        return in_array($model->id, [...rootUsers(), $user->id], true);
    }

    protected function canAny(Authenticatable $user, ?User $model, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $this->ownsOrAll($user, $model);
            }
        }

        return false;
    }
}
