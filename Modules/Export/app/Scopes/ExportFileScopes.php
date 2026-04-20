<?php
namespace Modules\Export\App\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait ExportFileScopes
{
    public function scopeRelated(Builder $builder): void
    {
        $user = auth()->user();

        // [1] User can view ALL export-files globally — no restrictions needed.
        if ($user->can('view-all-export-file')) {
            return;
        }

        // [2] User has no export-file permission at all — block everything.
        if (!$user->can('view-own-export-file')) {
            $builder->whereRaw('1 = 0');
            return;
        }

        // [3] User can view own export-files — restrict to created by them
        $builder->where('created_by', $user->id);
    }

}
