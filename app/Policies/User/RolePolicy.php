<?php

namespace App\Policies\User;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param Role|null $role
     * @return bool
     */
    public function view(User $user, ?Role $role = null): bool
    {
        if (!($user->can('view-all-role') || $user->can('view-own-role'))) {
            return false;
        }

        return !$role || $this->checkUser($user, $role);
    }

    /**
     * @param User $user
     * @param Role|null $role
     * @return bool
     */
    public function create(User $user, ?Role $role = null): bool
    {
        if (!$user->can('create-role')) {
            return false;
        }

        return !$role || $this->checkUser($user, $role);
    }

    /**
     * @param User $user
     * @param Role $role
     * @return bool
     */
    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'root' || !$user->can('update-role')) {
            return false;
        }

        return $this->checkUser($user, $role);
    }

    /**
     * @param User $user
     * @param Role $role
     * @return bool
     */
    public function delete(User $user, Role $role): bool
    {
        if (!$user->can('delete-role')) {
            return false;
        }

        return $this->checkUser($user, $role) && $role->name !== 'root';
    }

    /**
     * @param User $user
     * @param Role $role
     * @return bool
     */
    public function checkUser(User $user, Role $role): bool
    {
        return $user->can('view-all-role') || $role->created_by === $user->id;
    }
}
