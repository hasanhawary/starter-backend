<?php

namespace App\Policies\User;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    public function view(Admin $user, ?Admin $model = null): bool
    {
        return $this->canAct($user, $model, [
            'view-all-admin',
            'view-own-admin',
        ]);
    }

    public function create(Admin $user, ?Admin $model = null): bool
    {
        return $user->can('create-admin') && $this->ownsOrAll($user, $model);
    }

    public function update(Admin $user, Admin $model): bool
    {
        if (! $user->can('update-admin')) {
            return false;
        }

        if ($this->isProtectedAdmin($model, $user)) {
            return false;
        }

        return $this->ownsOrAll($user, $model);
    }

    public function delete(Admin $user, ?Admin $model = null): bool
    {
        if (! $user->can('delete-admin')) {
            return false;
        }

        if ($model && $this->isProtectedAdmin($model, $user)) {
            return false;
        }

        return $this->ownsOrAll($user, $model);
    }

    public function restore(Admin $user, ?Admin $model = null): bool
    {
        return $user->can('restore-admin') && $this->ownsOrAll($user, $model);
    }

    public function forceDelete(Admin $user, Admin $model): bool
    {
        if (! $user->can('force-delete-admin')) {
            return false;
        }

        return ! $this->isProtectedAdmin($model, $user) && $this->ownsOrAll($user, $model);
    }

    public function toggleActive(Admin $user, Admin $model): bool
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
    protected function ownsOrAll(Admin $user, ?Admin $model): bool
    {
        return !$model
            || $user->can('view-all-admin')
            || $model->created_by === $user->id;
    }

    protected function isProtectedAdmin(Admin $model, Admin $user): bool
    {
        return in_array($model->id, [...rootAdmins(), $user->id], true);
    }

    protected function canAct(Admin $user, ?Admin $model, array $permissions): bool {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $this->ownsOrAll($user, $model);
            }
        }

        return false;
    }
}
