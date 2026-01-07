<?php

namespace App\Policies\Central\Admin;

use App\Models\Central\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * @param Admin $admin
     * @param  ?Admin $adminModel
     * @return bool
     */
    public function view(Admin $admin, ?Admin $adminModel = null): bool
    {
        return ($admin->can('view-all-admin') || $admin->can('view-own-admin')) &&
            (!$adminModel || $this->checkAdmin($admin, $adminModel));
    }

    /**
     * @param Admin $admin
     * @param Admin|null $adminModel
     * @return bool
     */
    public function create(Admin $admin, ?Admin $adminModel = null): bool
    {
        return $admin->can('create-admin') && (!$adminModel || $this->checkAdmin($admin, $adminModel));
    }

    /**
     * @param Admin $admin
     * @param Admin $adminModel
     * @return bool
     */
    public function update(Admin $admin, Admin $adminModel): bool
    {
        return $admin->can('update-admin') &&
            $this->checkAdmin($admin, $adminModel) &&
            !in_array($adminModel->id, [...rootAdmins(), auth()->id()], false);
    }

    /**
     * @param Admin $admin
     * @param Admin|null $adminModel
     * @return bool
     */
    public function delete(Admin $admin, ?Admin $adminModel = null): bool
    {
        if (!$admin->can('delete-admin')) {
            return false;
        }

        return !$adminModel || ($this->checkAdmin($admin, $adminModel) && !in_array($adminModel?->id, [...rootAdmins(), auth()->id()], false));
    }

    /**
     * @param Admin $admin
     * @param Admin|null $adminModel
     * @return bool
     */
    public function restore(Admin $admin, ?Admin $adminModel = null): bool
    {
        if (!$admin->can('restore-admin')) {
            return false;
        }

        return !$adminModel || $this->checkAdmin($admin, $adminModel);
    }

    /**
     * @param Admin $admin
     * @param Admin $adminModel
     * @return bool
     */
    public function checkAdmin(Admin $admin, Admin $adminModel): bool
    {
        return $admin->can('view-all-admin') || $adminModel->created_by === $admin->id;
    }
}
