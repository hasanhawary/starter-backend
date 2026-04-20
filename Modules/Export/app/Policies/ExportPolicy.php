<?php

namespace Modules\Export\app\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Export\App\Models\ExportFile;

class ExportPolicy
{
    use HandlesAuthorization;

    public function view(Authenticatable $user, ExportFile $model): bool
    {
        return $this->canAccess($user, $model);
    }

    public function viewAny(Authenticatable $user): bool
    {
        return $user->canAny(['view-all-export-file', 'view-own-export-file']);
    }

    public function delete(Authenticatable $user, ExportFile $model): bool
    {
        return $this->canPerform($user, $model, 'delete-export-file');
    }

    public function restore(Authenticatable $user, ExportFile $model): bool
    {
        return $this->canPerform($user, $model, 'restore-export-file');
    }

    public function forceDelete(Authenticatable $user, ExportFile $model): bool
    {
        return $this->canPerform($user, $model, 'force-delete-export-file');
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function canPerform(Authenticatable $user, ExportFile $model, string $permission): bool
    {
        return $user->can($permission) && $this->canAccess($user, $model);
    }

    private function canAccess(Authenticatable $user, ExportFile $model): bool
    {
        if ($user->can('view-all-export-file')) {
            return true;
        }

        if ($user->can('view-own-export-file')) {
            return $this->userOwnsExportFile($user, $model);
        }

        return false;
    }

    private function userOwnsExportFile(Authenticatable $user, ExportFile $model): bool
    {
        if ((int) $model->created_by === (int) $user->id) {
            return true;
        }

        return $user->locationIds()->contains($model->location_id);
    }
}
