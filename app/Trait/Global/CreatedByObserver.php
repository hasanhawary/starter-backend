<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Model;

trait CreatedByObserver
{
    public static function bootCreatedByObserver(): void
    {
        static::creating(static fn(Model $model) => $model->created_by = auth()->id());
    }
}
