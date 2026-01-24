<?php

namespace App\Policies\User;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(Admin $admin, ?User $model = null): bool
    {
        return $this->canAct($admin, $model, [
            'view-all-user',
            'view-own-user',
        ]);
    }

    public function create(Admin $admin, ?User $model = null): bool
    {
        return $admin->can('create-user') && $this->ownsOrAll($admin, $model);
    }

    public function update(Admin $admin, User $model): bool
    {
        if (! $admin->can('update-user')) {
            return false;
        }

        if ($this->isProtectedUser($model, $admin)) {
            return false;
        }

        return $this->ownsOrAll($admin, $model);
    }

    public function delete(Admin $admin, ?User $model = null): bool
    {
        if (! $admin->can('delete-user')) {
            return false;
        }

        if ($model && $this->isProtectedUser($model, $admin)) {
            return false;
        }

        return $this->ownsOrAll($admin, $model);
    }

    public function restore(Admin $admin, ?User $model = null): bool
    {
        return $admin->can('restore-user') && $this->ownsOrAll($admin, $model);
    }

    public function forceDelete(Admin $admin, ?User $model = null): bool
    {
        return $admin->can('force-delete-user') && $this->ownsOrAll($admin, $model);
    }

    public function toggleActive(Admin $admin, ?User $model = null): bool
    {
        return $admin->can('toggle-active-user') && $this->ownsOrAll($admin, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function canAct(Admin $admin, ?User $model, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($admin->can($permission)) {
                if ($permission === 'view-own-user' && $model && $model->created_by !== $admin->id) {
                    continue;
                }
                return true;
            }
        }

        return false;
    }

    private function ownsOrAll(Admin $admin, ?User $model = null): bool
    {
        if (!$model) {
            return true;
        }

        if ($admin->can('view-all-user') || $admin->can('create-user') || $admin->can('update-user') || $admin->can('delete-user')) {
            return true;
        }

        return $model->created_by === $admin->id;
    }

    private function isProtectedUser(User $user, Admin $admin): bool
    {
        // Prevent admin from modifying root users
        return $user->hasRole('root') && !$admin->hasRole('root');
    }
}
