<?php

namespace App\Scopes\Central\User;

use Illuminate\Database\Eloquent\Builder;

trait AdminScopes
{
    public function scopeRelated(Builder $builder): void
    {
        $builder->when(!auth()->user()->can('view-all-admin'), function ($subQuery) {
            $subQuery->where('created_by', auth()->id());
        });
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
