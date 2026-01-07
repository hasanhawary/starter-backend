<?php

namespace App\Policies\Central\Admin;

use App\Models\Central\Role;
use App\Models\Central\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * @param Admin $admin
     * @param Role|null $role
     * @return bool
     */
    public function view(Admin $admin, ?Role $role = null): bool
    {
        if (!($admin->can('view-all-role') || $admin->can('view-own-role'))) {
            return false;
        }

        return !$role || $this->checkAdmin($admin, $role);
    }

    /**
     * @param Admin $admin
     * @param Role|null $role
     * @return bool
     */
    public function create(Admin $admin, ?Role $role = null): bool
    {
        if (!$admin->can('create-role')) {
            return false;
        }

        return !$role || $this->checkAdmin($admin, $role);
    }

    /**
     * @param Admin $admin
     * @param Role $role
     * @return bool
     */
    public function update(Admin $admin, Role $role): bool
    {
        if ($role->name === 'root' || !$admin->can('update-role')) {
            return false;
        }

        return $this->checkAdmin($admin, $role);
    }

    /**
     * @param Admin $admin
     * @param Role $role
     * @return bool
     */
    public function delete(Admin $admin, Role $role): bool
    {
        if (!$admin->can('delete-role')) {
            return false;
        }

        return $this->checkAdmin($admin, $role) && $role->name !== 'root';
    }

    /**
     * @param Admin $admin
     * @param Role $role
     * @return bool
     */
    public function checkAdmin(Admin $admin, Role $role): bool
    {
        return $admin->can('view-all-role') || $role->created_by === $admin->id;
    }
}
