<?php

namespace App\Policies\Central\Subscription;

use App\Models\Central\Admin;
use App\Models\Central\Plan;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;

    public function view(Admin $admin, ?Plan $model = null): bool
    {
        return $this->canAct($admin, $model, [
            'view-all-plan',
            'view-own-plan',
        ]);
    }

    public function create(Admin $admin, ?Plan $model = null): bool
    {
        return $admin->can('create-plan') && $this->ownsOrAll($admin, $model);
    }

    public function update(Admin $admin, Plan $model): bool
    {
        return $admin->can('update-plan') && $this->ownsOrAll($admin, $model);
    }

    public function delete(Admin $admin, ?Plan $model = null): bool
    {
        return $admin->can('delete-plan') && $this->ownsOrAll($admin, $model);
    }

    public function restore(Admin $admin, ?Plan $model = null): bool
    {
        return $admin->can('restore-plan') && $this->ownsOrAll($admin, $model);
    }

    public function forceDelete(Admin $admin, Plan $model): bool
    {
        return $admin->can('force-delete-plan') && $this->ownsOrAll($admin, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(Admin $admin, ?Plan $model): bool
    {
        return !$model
            || $admin->can('view-all-plan')
            || $model->created_by === $admin->id;
    }

    protected function canAct(Admin $admin, ?Plan $model, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($admin->can($permission)) {
                return $this->ownsOrAll($admin, $model);
            }
        }

        return false;
    }
}
