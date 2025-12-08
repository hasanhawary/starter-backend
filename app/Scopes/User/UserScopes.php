<?php

namespace App\Scopes\User;

use Illuminate\Database\Eloquent\Builder;

trait UserScopes
{
    public function scopeRelated(Builder $builder): void
    {
        $builder->when(!auth()->user()->can('view-all-user'), function ($subQuery) {
            $subQuery->where('created_by', auth()->id());
        })
            ->excludeLoggedInUser()
            ->excludeRoot();
    }

    public function scopeExcludeLoggedInUser(Builder $query): Builder
    {
        return $query->where('id', '!=', auth()->id());
    }

    public function scopeExcludeRoot(Builder $query): Builder
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', '!=', 'root');
        });
    }

    public function scopeWithRole(Builder $query, ?string $role = null): Builder
    {
        return $query->when($role, function ($subQuery) use ($role) {
            $subQuery->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        });
    }
}
