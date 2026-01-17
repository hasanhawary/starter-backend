<?php

namespace App\Policies\Central\Subscription;

use App\Models\Central\Admin;
use App\Models\Central\SubscriptionUsage;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionUsagePolicy
{
    use HandlesAuthorization;

    public function view(Admin $admin, ?SubscriptionUsage $model = null): bool
    {
        return $this->canAct($admin, $model, [
            'view-all-subscription-usage',
            'view-own-subscription-usage',
        ]);
    }

    public function create(Admin $admin, ?SubscriptionUsage $model = null): bool
    {
        return $admin->can('create-subscription-usage') && $this->ownsOrAll($admin, $model);
    }

    public function update(Admin $admin, SubscriptionUsage $model): bool
    {
        return $admin->can('update-subscription-usage') && $this->ownsOrAll($admin, $model);
    }

    public function delete(Admin $admin, ?SubscriptionUsage $model = null): bool
    {
        return $admin->can('delete-subscription-usage') && $this->ownsOrAll($admin, $model);
    }

    public function restore(Admin $admin, ?SubscriptionUsage $model = null): bool
    {
        return $admin->can('restore-subscription-usage') && $this->ownsOrAll($admin, $model);
    }

    public function forceDelete(Admin $admin, SubscriptionUsage $model): bool
    {
        return $admin->can('force-delete-subscription-usage') && $this->ownsOrAll($admin, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(Admin $admin, ?SubscriptionUsage $model): bool
    {
        return !$model
            || $admin->can('view-all-subscription-usage')
            || $model->created_by === $admin->id;
    }

    protected function canAct(Admin $admin, ?SubscriptionUsage $model, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($admin->can($permission)) {
                return $this->ownsOrAll($admin, $model);
            }
        }

        return false;
    }
}
