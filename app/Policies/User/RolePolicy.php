<?php

namespace App\Policies\User;

use App\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;

class RolePolicy
{
    use HandlesAuthorization;

    const string ROOT = 'root';

    public function view(User $user, ?Role $role = null): bool
    {
        return $this->canAct($user, $role, [
            'view-all-role',
            'view-own-role',
        ]);
    }

    public function create(User $user, ?Role $role = null): bool
    {
        return $user->can('create-role') && $this->ownsOrAll($user, $role);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('update-role')
            && ! $this->isProtectedRole($role)
            && $this->ownsOrAll($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('delete-role')
            && ! $this->isProtectedRole($role)
            && $this->ownsOrAll($user, $role);
    }

    public function toggleActive(User $user, Role $role): bool
    {
        return $user->can('toggle-active-role')
            && ! $this->isProtectedRole($role)
            && $this->ownsOrAll($user, $role);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(User $user, ?Role $role): bool
    {
        return ! $role
            || $user->can('view-all-role')
            || $role->created_by === $user->id;
    }

    /**
     * Check if the role is protected (root or assigned to current user)
     */
    protected function isProtectedRole(Role $role): bool
    {
        return $role->name === self::ROOT;
    }

    /**
     * Check permissions + ownership
     */
    protected function canAct(User $user, ?Role $role, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $this->ownsOrAll($user, $role);
            }
        }

        return false;
    }
}
