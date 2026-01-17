<?php

namespace App\Policies\Central\Subscription;

use App\Models\Central\Admin;
use App\Models\Central\PlanFeature;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanFeaturePolicy
{
    use HandlesAuthorization;

    public function view(Admin $admin, ?PlanFeature $model = null): bool
    {
        return $this->canAct($admin, $model, [
            'view-all-plan-feature',
            'view-own-plan-feature',
        ]);
    }

    public function create(Admin $admin, ?PlanFeature $model = null): bool
    {
        return $admin->can('create-plan-feature') && $this->ownsOrAll($admin, $model);
    }

    public function update(Admin $admin, PlanFeature $model): bool
    {
        return $admin->can('update-plan-feature') && $this->ownsOrAll($admin, $model);
    }

    public function delete(Admin $admin, ?PlanFeature $model = null): bool
    {
        return $admin->can('delete-plan-feature') && $this->ownsOrAll($admin, $model);
    }

    public function restore(Admin $admin, ?PlanFeature $model = null): bool
    {
        return $admin->can('restore-plan-feature') && $this->ownsOrAll($admin, $model);
    }

    public function forceDelete(Admin $admin, PlanFeature $model): bool
    {
        return $admin->can('force-delete-plan-feature') && $this->ownsOrAll($admin, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(Admin $admin, ?PlanFeature $model): bool
    {
        return !$model
            || $admin->can('view-all-plan-feature')
            || $model->created_by === $admin->id;
    }

    protected function canAct(Admin $admin, ?PlanFeature $model, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($admin->can($permission)) {
                return $this->ownsOrAll($admin, $model);
            }
        }

        return false;
    }
}
