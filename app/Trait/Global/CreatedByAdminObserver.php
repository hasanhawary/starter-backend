<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Model;

trait CreatedByAdminObserver
{
    public static function bootCreatedByAdminObserver(): void
    {
        static::creating(static fn(Model $model) => $model->created_by = auth('admin')->id());
    }
}
