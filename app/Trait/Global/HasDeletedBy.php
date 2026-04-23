<?php

namespace App\Trait\Global;

use Illuminate\Support\Facades\Auth;

trait HasDeletedBy
{
    public function initializeHasDeletedBy(): void
    {
        $this->fillable[] = 'deleted_by';
    }

    public static function bootHasDeletedBy(): void
    {
        static::deleting(function ($model) {
            $model->deleted_by = Auth::id();
            $model->saveQuietly();
        });

        static::restoring(function ($model) {
            $model->deleted_by = null;
            $model->saveQuietly();
        });
    }
}
