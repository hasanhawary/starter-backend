<?php

namespace App\Policies\User;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;

class AdminPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ?Admin $model = null): bool
    {
        return $this->canAct($user, $model, [
            'view-all-admin',
            'view-own-admin',
        ]);
    }

    public function create(User $user, ?Admin $model = null): bool
    {
        return $user->can('create-admin') && $this->ownsOrAll($user, $model);
    }

    public function update(User $user, Admin $model): bool
    {
        if (! $user->can('update-admin')) {
            return false;
        }

        if ($this->isProtectedAdmin($model, $user)) {
            return false;
        }

        return $this->ownsOrAll($user, $model);
    }

    public function delete(User $user, ?Admin $model = null): bool
    {
        if (! $user->can('delete-admin')) {
            return false;
        }

        if ($model && $this->isProtectedAdmin($model, $user)) {
            return false;
        }

        return $this->ownsOrAll($user, $model);
    }

    public function restore(User $user, ?Admin $model = null): bool
    {
        return $user->can('restore-admin') && $this->ownsOrAll($user, $model);
    }

    public function forceDelete(User $user, Admin $model): bool
    {
        if (! $user->can('force-delete-admin')) {
            return false;
        }

        return ! $this->isProtectedAdmin($model, $user) && $this->ownsOrAll($user, $model);
    }

    public function toggleActive(User $user, Admin $model): bool
    {
        if (! $user->can('toggle-active-admin')) {
            return false;
        }

        return ! $this->isProtectedAdmin($model, $user) && $this->ownsOrAll($user, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(User $user, ?Admin $model): bool
    {
        return !$model
            || $user->can('view-all-admin')
            || $model->created_by === $user->id;
    }

    protected function isProtectedAdmin(Admin $model, User $user): bool
    {
        return in_array($model->id, [...rootAdmins(), $user->id], true);
    }

    protected function canAct(User $user, ?Admin $model, array $permissions): bool {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $this->ownsOrAll($user, $model);
            }
        }

        return false;
    }
}
