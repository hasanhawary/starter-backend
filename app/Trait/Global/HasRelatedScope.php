<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Builder;

trait HasRelatedScope
{
    /**
     *  Scope a query to only include records related to the authenticated user.
     */
    public function scopeRelated(Builder $query): Builder
    {
        return $query->where('created_by', auth()->id());
    }
}
