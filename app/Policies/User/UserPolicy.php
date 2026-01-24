<?php

namespace App\Policies\User;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    const string ROOT = 'root';

    public function view(User $user, ?User $model = null): bool
    {
        return $this->canAct($user, $model, [
            'view-all-user',
            'view-own-user',
        ]);
    }

    public function create(User $user, ?User $model = null): bool
    {
        return $user->can('create-user') && $this->ownsOrAll($user, $model);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('update-user')
            && ! $this->isProtectedUser($model)
            && $this->ownsOrAll($user, $model);
    }

    public function delete(User $user, ?User $model = null): bool
    {
        return $user->can('delete-user')
            && (! $model || ! $this->isProtectedUser($model))
            && $this->ownsOrAll($user, $model);
    }

    public function restore(User $user, ?User $model = null): bool
    {
        return $user->can('restore-user') && $this->ownsOrAll($user, $model);
    }

    public function forceDelete(User $user, ?User $model = null): bool
    {
        return $user->can('force-delete-user') && $this->ownsOrAll($user, $model);
    }

    public function toggleActive(User $user, ?User $model = null): bool
    {
        return $user->can('toggle-active-user')
            && (! $model || ! $this->isProtectedUser($model)) && $this->ownsOrAll($user, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(User $user, ?User $model): bool
    {
        return !$model
            || $user->can('view-all-user')
            || $model->created_by === $user->id;
    }

    protected function isProtectedUser(User $model): bool
    {
        // Prevent actions on root users
        return $model->hasRole(self::ROOT);
    }

    protected function canAct(User $user, ?User $model, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $this->ownsOrAll($user, $model);
            }
        }

        return false;
    }
}
