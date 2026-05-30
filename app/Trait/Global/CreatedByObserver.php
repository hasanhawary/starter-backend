<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Model;

trait CreatedByObserver
{
    public static function bootCreatedByObserver(): void
    {
        static::creating(function (Model $model) {
            try {
                $model->created_by = auth()->id();
            } catch (\Throwable) {
            }
        });
    }
}
