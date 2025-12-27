<?php

namespace App\Scopes\User;

use Illuminate\Database\Eloquent\Builder;

trait RoleScopes
{
    public function scopeRelated(Builder $builder): void
    {
        $builder->when(!auth()->user()->can('view-all-role'), function ($subQuery) {
            $subQuery->where('created_by', auth('admin')->id());
        })
            ->excludeRoot()
            ->excludeLoggedInRole();
    }

    public function scopeExcludeRoot(Builder $query): Builder
    {
        return $query->where('name', '!=', 'root');
    }

    public function scopeExcludeLoggedInRole(Builder $query): Builder
    {
        return $query->whereNotIn('id', auth('admin')->user()->roles()->pluck('id')->toArray());
    }
}
