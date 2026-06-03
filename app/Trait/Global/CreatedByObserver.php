<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Model;

trait CreatedByObserver
{
    public static function bootCreatedByObserver(): void
    {
        $column = property_exists(static::class, 'createdByColumn')
            ? static::$createdByColumn
            : 'created_by';

        static::creating(static function (Model $model) use ($column): void {
            if (auth()->check()) {
                $model->{$column} = auth()->id();
            }
        });
    }
}
