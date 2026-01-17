<?php

namespace App\Policies\Central\Subscription;

use App\Models\Central\Admin;
use App\Models\Central\Subscription;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function view(Admin $admin, ?Subscription $model = null): bool
    {
        return $this->canAct($admin, $model, [
            'view-all-subscription',
            'view-own-subscription',
        ]);
    }

    public function create(Admin $admin, ?Subscription $model = null): bool
    {
        return $admin->can('create-subscription') && $this->ownsOrAll($admin, $model);
    }

    public function update(Admin $admin, Subscription $model): bool
    {
        return $admin->can('update-subscription') && $this->ownsOrAll($admin, $model);
    }

    public function delete(Admin $admin, ?Subscription $model = null): bool
    {
        return $admin->can('delete-subscription') && $this->ownsOrAll($admin, $model);
    }

    public function restore(Admin $admin, ?Subscription $model = null): bool
    {
        return $admin->can('restore-subscription') && $this->ownsOrAll($admin, $model);
    }

    public function forceDelete(Admin $admin, Subscription $model): bool
    {
        return $admin->can('force-delete-subscription') && $this->ownsOrAll($admin, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(Admin $admin, ?Subscription $model): bool
    {
        return !$model
            || $admin->can('view-all-subscription')
            || $model->created_by === $admin->id;
    }

    protected function canAct(Admin $admin, ?Subscription $model, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($admin->can($permission)) {
                return $this->ownsOrAll($admin, $model);
            }
        }

        return false;
    }
}
